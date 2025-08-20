<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {

    private $token = 'mysecrettoken123'; // Token for Authorization: Bearer <token>

    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model', 'task');
        $this->load->model('Tag_model',  'tag');
        $this->output->set_content_type('application/json');

        // Simple token auth for all endpoints (skip OPTIONS)
        $method = $this->input->method(TRUE);
        if ($method !== 'OPTIONS') {
            $headers = $this->input->request_headers();
            $auth = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
            if (!$auth || $auth !== 'Bearer ' . $this->token) {
                $this->output->set_status_header(401)
                    ->set_output(json_encode(['status' => 401, 'error' => 'Unauthorized']))->_display();
                exit;
            }
        }
    }

    /* ------------ Validation helper ------------ */
    private function validate_task($data, $is_update = false) {
        $errors = [];

        // Required: title (create), optional in update but if present must not be empty.
        if (!$is_update) {
            if (empty($data['title'])) $errors['title'] = 'Title is required';
        } else {
            if (array_key_exists('title', $data) && trim($data['title']) === '') {
                $errors['title'] = 'Title cannot be empty';
            }
        }

        // Enumerations
        $statuses  = ['pending', 'in_progress', 'completed'];
        $priorities= ['low', 'medium', 'high'];

        if (!empty($data['status'])   && !in_array($data['status'], $statuses))     $errors['status'] = 'Invalid status';
        if (!empty($data['priority']) && !in_array($data['priority'], $priorities)) $errors['priority'] = 'Invalid priority';

        // Date format (YYYY-MM-DD)
        if (isset($data['due_date']) && $data['due_date'] !== null && $data['due_date'] !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
                $errors['due_date'] = 'Invalid date format (YYYY-MM-DD)';
            }
        }

        return $errors;
    }

    /* ------------ GET /tasks ------------ */
    public function index() {
        $filters = $this->input->get();
        $page    = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit   = isset($filters['limit']) ? max(1, (int)$filters['limit']) : 10;
        $offset  = ($page - 1) * $limit;
        $sort_by = isset($filters['sort_by']) ? $filters['sort_by'] : 'created_at';
        $sort_dir= isset($filters['sort_dir']) ? $filters['sort_dir'] : 'DESC';

        $result = $this->task->get_all($filters, $limit, $offset, $sort_by, $sort_dir);

        $this->output->set_status_header(200)
            ->set_output(json_encode([
                'status' => 200,
                'tasks'  => $result['tasks'],
                'total'  => $result['total'],
                'page'   => $page,
                'limit'  => $limit
            ]));
    }

    /* ------------ GET /tasks/{id} ------------ */
    public function show($id) {
        $task = $this->task->get($id);
        if ($task) {
            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'data' => $task]));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found']));
        }
    }

    /* ------------ POST /tasks ------------ */
    public function store() {
        $data = json_decode($this->input->raw_input_stream, true) ?: [];

        $errors = $this->validate_task($data, false);
        if (!empty($errors)) {
            $this->output->set_status_header(422)
                ->set_output(json_encode(['status' => 422, 'errors' => $errors]));
            return;
        }

        // normalize optional fields
        if (!isset($data['due_date']) || $data['due_date'] === '') $data['due_date'] = null;

        // pluck tags, can be tag IDs or tag names
        $tag_ids = [];
        if (!empty($data['tags'])) {
            // Accept either ["bug","urgent"] or [1,3]
            foreach ($data['tags'] as $tg) {
                if (is_numeric($tg)) {
                    $tag_ids[] = (int)$tg;
                } else {
                    $tag_ids[] = $this->tag->get_or_create_by_name(trim($tg));
                }
            }
        }
        unset($data['tags']);

        $id = $this->task->insert($data);
        $this->task->sync_tags($id, $tag_ids);

        // audit log
        $this->db->insert('task_logs', [
            'task_id'    => $id,
            'action'     => 'create',
            'changes'    => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->output->set_status_header(201)
            ->set_output(json_encode(['status' => 201, 'message' => 'Task created', 'id' => $id]));
    }

    /* ------------ PUT /tasks/{id} ------------ */
    public function update($id) {
        $data = json_decode($this->input->raw_input_stream, true) ?: [];

        $errors = $this->validate_task($data, true);
        if (!empty($errors)) {
            $this->output->set_status_header(422)
                ->set_output(json_encode(['status' => 422, 'errors' => $errors]));
            return;
        }

        if (array_key_exists('due_date', $data) && $data['due_date'] === '') $data['due_date'] = null;

        // tags sync if provided
        $tagsProvided = array_key_exists('tags', $data);
        $tag_ids = [];
        if ($tagsProvided) {
            foreach ($data['tags'] as $tg) {
                if (is_numeric($tg)) $tag_ids[] = (int)$tg;
                else $tag_ids[] = $this->tag->get_or_create_by_name(trim($tg));
            }
            unset($data['tags']);
        }

        $ok = $this->task->update($id, $data);
        if ($ok) {
            if ($tagsProvided) $this->task->sync_tags($id, $tag_ids);

            $this->db->insert('task_logs', [
                'task_id'    => $id,
                'action'     => 'update',
                'changes'    => json_encode($data),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'message' => 'Task updated']));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found']));
        }
    }

    /* ------------ DELETE /tasks/{id} (soft) ------------ */
    public function delete($id) {
        $ok = $this->task->delete($id);
        if ($ok) {
            $this->db->insert('task_logs', [
                'task_id'    => $id,
                'action'     => 'delete',
                'changes'    => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'message' => 'Task deleted']));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found']));
        }
    }

    /* ------------ PATCH/POST /tasks/{id}/restore ------------ */
    public function restore($id) {
        $method = $this->input->method(TRUE);
        if (!in_array($method, ['PATCH','POST'])) {
            $this->output->set_status_header(405)
                ->set_output(json_encode(['status' => 405, 'error' => 'Method not allowed']));
            return;
        }

        $ok = $this->task->restore($id);
        if ($ok) {
            $this->db->insert('task_logs', [
                'task_id'    => $id,
                'action'     => 'restore',
                'changes'    => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'message' => 'Task restored']));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found or already active']));
        }
    }

    /* ------------ PATCH/POST /tasks/{id}/toggle-status ------------ */
    public function toggle_status($id) {
        $method = $this->input->method(TRUE);
        if (!in_array($method, ['PATCH','POST'])) {
            $this->output->set_status_header(405)
                ->set_output(json_encode(['status' => 405, 'error' => 'Method not allowed']));
            return;
        }

        $task = $this->task->get($id);
        if (!$task) {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found']));
            return;
        }

        $flow = ['pending', 'in_progress', 'completed'];
        $pos  = array_search($task['status'], $flow);
        $next = $flow[(($pos === false ? -1 : $pos) + 1) % count($flow)];

        $this->task->update($id, ['status' => $next]);

        $this->db->insert('task_logs', [
            'task_id'    => $id,
            'action'     => 'toggle_status',
            'changes'    => json_encode(['from' => $task['status'], 'to' => $next]),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->output->set_status_header(200)
            ->set_output(json_encode(['status' => 200, 'message' => 'Status updated', 'new_status' => $next]));
    }

}
