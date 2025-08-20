<?php
class Tags extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tag_model');
        header('Content-Type: application/json');
    }

    public function index()
    {
        $tags = $this->Tag_model->get_all();
        echo json_encode($tags);
    }

    // Display Tag by ID
    public function show($id)
    {
        $tag = $this->Tag_model->get_by_id($id);
        if ($tag) {
            echo json_encode($tag);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Tag not found"]);
        }
    }

    // Create New tag
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag name required']);
            return;
        }
        $id = $this->Tag_model->insert($data);
        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Tag created']);
    }


    // Update existing tag
    public function update($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->Tag_model->update_tag($id, $data);
        echo json_encode(['message' => 'Tag updated']);
    }

    // Delete Existing tag
    public function delete($id)
    {
        $this->Tag_model->delete_tag($id);
        echo json_encode(['message' => 'Tag deleted']);
    }
}
