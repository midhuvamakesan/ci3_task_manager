
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tasks extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model');
        $this->load->library('form_validation');
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }

    

    private function respond($data, $status = 200) {
        $this->output
             ->set_status_header($status)
             ->set_output(json_encode($data));
    }
    // list all tasks
    public function index() {
        $filters = $this->input->get();
        $limit   = $this->input->get('limit') ?? 10;
        $offset  = $this->input->get('offset') ?? 0;
        $sort    = $this->input->get('sort') ?? 'created_at';
        $order   = $this->input->get('order') ?? 'DESC';
        $tasks = $this->Task_model->get_all($filters, $limit, $offset, $sort, $order);
        return $this->respond($tasks);
    }
    //get task by ID
    /*  // GET /tasks/{id} */
    public function show($id) {
        $task = $this->Task_model->get_by_id($id);
        if ($task) return $this->respond($task);
        return $this->respond(['error' => 'Not Found'], 404);
    }

    // create task
    // POST /tasks
    public function store() {
        $data = json_decode($this->input->raw_input_stream, true);
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('title', 'Title', 'required|min_length[5]');
        $this->form_validation->set_rules('status', 'Status', 'in_list[pending,in_progress,completed]');
        $this->form_validation->set_rules('priority', 'Priority', 'in_list[low,medium,high]');
        if ($this->form_validation->run() === FALSE) {
            http_response_code(422);
            echo json_encode(['status' => 422, 'errors' => $this->form_validation->error_array()]);
            return;
        }
        
        $id = $this->Task_model->insert($data);
        http_response_code(201);
        echo json_encode(['status' => 201, 'message' => 'Task created', 'id' => $id]);
    }

    // update task
    // PUT /tasks/{id}
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$this->Task_model->get($id)) {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Task not found']);
            return;
        }

        $this->Task_model->update($id, $input);
        echo json_encode(['status' => 200, 'message' => 'Task updated']);
    }
    //delete task
    // DELETE /tasks/{id}
    public function delete($id) {
        if (!$this->Task_model->get($id)) {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Task not found']);
            return;
        }

        $this->Task_model->delete($id);
        echo json_encode(['status' => 200, 'message' => 'Task deleted']);
    }
}
