<?php
class Tag_model extends CI_Model
{
    private $table = 'tags';

    public function get_all()
    {
        return $this->db->get($this->table)->result_array();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_tag($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete_tag($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }
    public function get_or_create_by_name($name) {
        $tag = $this->db->where('name', $name)->get('tags')->row_array();
        if ($tag) return $tag['id'];
        $this->db->insert('tags', ['name' => $name]);
        return $this->db->insert_id();
    }

}
