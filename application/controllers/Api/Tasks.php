<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {
    private $token = 'mytoken2025'; // Hard code token for Authorization: Bearer 


    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model', 'task');
        $this->load->model('Tag_model',  'tag');
        $this->output->set_content_type('application/json');


        // Checks the Authentication

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

        // Required: Title only in Create
        if (!$is_update) {
            if (empty($data['title'])) $errors['title'] = 'Title is required';
        } else {
            if (array_key_exists('title', $data) && trim($data['title']) === '') {
                $errors['title'] = 'Title cannot be empty';
            }
        }


        // Enum data validation

        $statuses  = ['pending', 'in_progress', 'completed'];
        $priorities= ['low', 'medium', 'high'];

        if (!empty($data['status'])   && !in_array($data['status'], $statuses))     $errors['status'] = 'Invalid status';
        if (!empty($data['priority']) && !in_array($data['priority'], $priorities)) $errors['priority'] = 'Invalid priority';

        // Date format (YYYY-MM-DD) validation
        if (isset($data['due_date']) && $data['due_date'] !== null && $data['due_date'] !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
                $errors['due_date'] = 'Invalid date format (YYYY-MM-DD)';
            }
        }

        return $errors;
    }


    /* ------------ List out all Tasks using GET ------------ */

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


    /* ------------  Fetch the task by id(Single data) using GET ------------ */

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


    /* ------------ Create new Task or Store Task------------ */

    public function store() {
        $data = json_decode($this->input->raw_input_stream, true) ?: [];

        // Validation Check
        $errors = $this->validate_task($data, false);
        if (!empty($errors)) {
            $this->output->set_status_header(422)
                ->set_output(json_encode(['status' => 422, 'errors' => $errors]));
            return;
        }

        // Date Null
        if (!isset($data['due_date']) || $data['due_date'] === '') $data['due_date'] = null;

        $tag_ids = [];
        if (!empty($data['tags'])) {
            $numeric_tags = [];
            $new_tags = [];

            // Separate numeric IDs and new tag names
            foreach ($data['tags'] as $tg) {
                if (is_numeric($tg)) {
                    $numeric_tags[] = (int)$tg;
                } else {
                    $new_tags[] = trim($tg);
                }
            }

            // Validate numeric tag IDs exist in database
            if (!empty($numeric_tags)) {
                $this->db->where_in('id', $numeric_tags);
                $existing_tags = $this->db->get('tags')->result_array();
                $existing_tag_ids = array_column($existing_tags, 'id');

                $invalid_tags = array_diff($numeric_tags, $existing_tag_ids);
                if (!empty($invalid_tags)) {
                    $this->output->set_status_header(400)
                        ->set_output(json_encode([
                            'status' => 400,
                            'message' => 'Invalid tag IDs: ' . implode(',', $invalid_tags)
                        ]));
                    return;
                }
                $tag_ids = array_merge($tag_ids, $existing_tag_ids);
            }

            // Create new tags if any
            foreach ($new_tags as $tag_name) {
                $tag_ids[] = $this->tag->get_or_create_by_name($tag_name);
            }
        }

        unset($data['tags']);

        // Insert the task
        $id = $this->task->insert($data);

        // Sync tags
        $this->task->sync_tags($id, $tag_ids);

        // Insert into task logs
        $this->db->insert('task_logs', [
            'task_id'    => $id,
            'action'     => 'create',
            'changes'    => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->output->set_status_header(201)
            ->set_output(json_encode(['status' => 201, 'message' => 'Task created', 'id' => $id]));
    }


    /* ------------ Update the task using PUT method------------ */
    public function update($id) {
        $data = json_decode($this->input->raw_input_stream, true) ?: [];

        // Validation
        $errors = $this->validate_task($data, true);
        if (!empty($errors)) {
            $this->output->set_status_header(422)
                ->set_output(json_encode(['status' => 422, 'errors' => $errors]));
            return;
        }

        // Ensure due_date is null if empty
        if (array_key_exists('due_date', $data) && $data['due_date'] === '') $data['due_date'] = null;

        // Tags processing
        $tagsProvided = array_key_exists('tags', $data);
        $tag_ids = [];
        if ($tagsProvided) {
            $numeric_tags = [];
            $new_tags = [];

            foreach ($data['tags'] as $tg) {
                if (is_numeric($tg)) $numeric_tags[] = (int)$tg;
                else $new_tags[] = trim($tg);
            }

            // Validate numeric tags exist
            if (!empty($numeric_tags)) {
                $this->db->where_in('id', $numeric_tags);
                $existing_tags = $this->db->get('tags')->result_array();
                $existing_tag_ids = array_column($existing_tags, 'id');

                $invalid_tags = array_diff($numeric_tags, $existing_tag_ids);
                if (!empty($invalid_tags)) {
                    $this->output->set_status_header(400)
                        ->set_output(json_encode([
                            'status' => 400,
                            'message' => 'Invalid tag IDs: ' . implode(',', $invalid_tags)
                        ]));
                    return;
                }

                $tag_ids = array_merge($tag_ids, $existing_tag_ids);
            }

            // Create new tags if provided
            foreach ($new_tags as $tag_name) {
                $tag_ids[] = $this->tag->get_or_create_by_name($tag_name);
            }

            unset($data['tags']);
        }

        // Update task
        $updated = $this->task->update($id, $data);
        if ($updated) {
            if ($tagsProvided) $this->task->sync_tags($id, $tag_ids);

            // Task logs
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


    /* ------------ Delete task with ID (Soft delete) Using DELETE ------------ */
    public function delete($id) {
        $deleted = $this->task->delete($id);
        // Insert into task logs table with delete action
        if ($deleted) {
            $this->db->insert('task_logs', [
                'task_id'    => $id,
                'action'     => 'delete',
                'changes'    => 'Soft deleted the Task',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'message' => 'Task deleted']));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found']));
        }
    }

    /* ------------ restore the soft delete using PATCH------------ */
    public function restore($id) {
        $method = $this->input->method(TRUE);
        if (!in_array($method, ['PATCH','POST'])) {
            $this->output->set_status_header(405)
                ->set_output(json_encode(['status' => 405, 'error' => 'Method not allowed']));
            return;
        }

        $restored = $this->task->restore($id);
        // Insert into task logs table with Restore action
        if ($restored) {
            $this->db->insert('task_logs', [
                'task_id'    => $id,
                'action'     => 'restore',
                'changes'    => 'Restore soft delete',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->output->set_status_header(200)
                ->set_output(json_encode(['status' => 200, 'message' => 'Task restored']));
        } else {
            $this->output->set_status_header(404)
                ->set_output(json_encode(['status' => 404, 'error' => 'Task not found or already active']));
        }
    }

    /* ------------ Toggle-status using PATCH/POST ------------ */
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
        // Insert into task logs table with toggle status
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
