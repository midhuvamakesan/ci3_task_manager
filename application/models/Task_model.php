<?php
class Task_model extends CI_Model {

    private $table = 'tasks';

    /*public function get_all($filters = []) {
        $this->db->where('deleted_at IS NULL'); // exclude soft-deleted

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where('priority', $filters['priority']);
        }
        if (!empty($filters['due_date_from'])) {
            $this->db->where('due_date >=', $filters['due_date_from']);
        }
        if (!empty($filters['due_date_to'])) {
            $this->db->where('due_date <=', $filters['due_date_to']);
        }
        if (!empty($filters['keyword'])) {
            $this->db->like('title', $filters['keyword']);
        }

        return $this->db->get($this->table)->result_array();
    }*/

    public function get_all($filters = [], $limit = 10, $offset = 0, $sort_by = 'created_at', $sort_dir = 'DESC') {
        $this->db->from($this->table);
         // soft delete handling
        if (!empty($filters['only_deleted']) && $filters['only_deleted'] === '1') {
            $this->db->where('deleted_at IS NOT NULL', null, false);
        } else {
            $this->db->where('deleted_at IS NULL', null, false);
        }

        // --- Filtering ---
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where('priority', $filters['priority']);
        }
        if (!empty($filters['due_date_from'])) {
            $this->db->where('due_date >=', $filters['due_date_from']);
        }
        if (!empty($filters['due_date_to'])) {
            $this->db->where('due_date <=', $filters['due_date_to']);
        }
        if (!empty($filters['keyword'])) {
            $this->db->like('title', $filters['keyword']);
        }

        $this->db->select('tasks.*');
        // tag filter (by tag id or name)
        $tagJoin = false;
        if (!empty($filters['tag_id'])) {
            $this->db->join('task_tags tt', 'tt.task_id = tasks.id', 'inner');
            $this->db->where('tt.tag_id', (int)$filters['tag_id']);
            $tagJoin = true;
        } elseif (!empty($filters['tag'])) {
            $this->db->join('task_tags tt', 'tt.task_id = tasks.id', 'inner');
            $this->db->join('tags t', 't.id = tt.tag_id', 'inner');
            $this->db->where('t.name', $filters['tag']);
            $tagJoin = true;
        }


        // Count before limit (for pagination)
        $count_query = clone $this->db;
        $total = $count_query->count_all_results('', FALSE);

        // sorting
        $allowed_sorts = ['created_at', 'due_date', 'priority'];
        if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'created_at';
        $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by($sort_by, $sort_dir);


        // pagination
        $this->db->limit($limit, $offset);

        // if we joined tags for filter, select distinct tasks
        if ($tagJoin) $this->db->group_by('tasks.id');

        $tasks = $this->db->get()->result_array();

        // attach tags per task
        foreach ($tasks as &$t) {
            $t['tags'] = $this->get_tags_for_task($t['id']);
        }

        return ['tasks' => $tasks, 'total' => $total];
    }


    public function get($id) {
         $row = $this->db->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row_array();
        if ($row) {
            $row['tags'] = $this->get_tags_for_task($id);
        }
        return $row;

    }

    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where('id', $id)
            ->update($this->table, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function restore($id) {
        return $this->db->where('id', $id)
            ->update($this->table, ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /* ---------- Tags helpers ---------- */

    public function get_tags_for_task($task_id) {
        return $this->db->select('t.id, t.name')
            ->from('task_tags tt')
            ->join('tags t', 't.id = tt.tag_id', 'inner')
            ->where('tt.task_id', $task_id)
            ->get()->result_array();
    }

    // detach all then attach given tag IDs
    public function sync_tags($task_id, $tag_ids = []) {
        $this->db->delete('task_tags', ['task_id' => $task_id]);
        if (empty($tag_ids)) return true;
        $batch = [];
        foreach ($tag_ids as $tid) {
            $batch[] = ['task_id' => $task_id, 'tag_id' => (int)$tid];
        }
        return $this->db->insert_batch('task_tags', $batch);
    }
}
