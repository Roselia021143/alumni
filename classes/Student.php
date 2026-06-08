<?php

require_once __DIR__ . '/StudentUser.php';

class Student
{
    private $conn;
    private $studentUser;
    private $hasProfileImageColumn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->studentUser = new StudentUser($conn);
    }

    public function all($keyword = '', $limit = null, $offset = 0)
    {
        $keyword = trim($keyword);
        $usePagination = $limit !== null;
        $limit = (int) $limit;
        $offset = (int) $offset;

        if ($keyword !== '') {
            $search = '%' . $keyword . '%';
            $sql = 'SELECT s.*, p.student_code AS parent_student_code
                 FROM students s
                 LEFT JOIN students p ON s.parent_student_id = p.id
                 WHERE s.student_code LIKE ?
                    OR s.first_name LIKE ?
                    OR s.last_name LIKE ?
                    OR CONCAT(s.first_name, " ", s.last_name) LIKE ?
                 ORDER BY s.student_code ASC';

            if ($usePagination) {
                $sql .= ' LIMIT ? OFFSET ?';
            }

            $stmt = $this->conn->prepare($sql);

            if ($usePagination) {
                $stmt->bind_param('ssssii', $search, $search, $search, $search, $limit, $offset);
            } else {
                $stmt->bind_param('ssss', $search, $search, $search, $search);
            }
        } else {
            $sql = 'SELECT s.*, p.student_code AS parent_student_code
                 FROM students s
                 LEFT JOIN students p ON s.parent_student_id = p.id
                 ORDER BY s.student_code ASC';

            if ($usePagination) {
                $sql .= ' LIMIT ? OFFSET ?';
            }

            $stmt = $this->conn->prepare($sql);

            if ($usePagination) {
                $stmt->bind_param('ii', $limit, $offset);
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $students = [];

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }

        $stmt->close();
        return $students;
    }

    public function countAll($keyword = '')
    {
        $keyword = trim($keyword);

        if ($keyword !== '') {
            $search = '%' . $keyword . '%';
            $stmt = $this->conn->prepare(
                'SELECT COUNT(*) AS total
                 FROM students
                 WHERE student_code LIKE ?
                    OR first_name LIKE ?
                    OR last_name LIKE ?
                    OR CONCAT(first_name, " ", last_name) LIKE ?'
            );
            $stmt->bind_param('ssss', $search, $search, $search, $search);
        } else {
            $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM students');
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? (int) $row['total'] : 0;
    }

    public function find($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $student;
    }

    public function findByCode($studentCode)
    {
        $stmt = $this->conn->prepare('SELECT * FROM students WHERE student_code = ? LIMIT 1');
        $stmt->bind_param('s', $studentCode);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $student;
    }

    public function getLineage($id)
    {
        $student = $this->findWithParent($id);

        if (!$student) {
            return null;
        }

        return [
            'student' => $student,
            'ancestors' => $this->getAncestors($student),
            'descendants' => $this->getDescendants((int) $student['id']),
        ];
    }

    public function getLineagePreview($id, $limit = 5)
    {
        $lineage = $this->getLineage($id);

        if (!$lineage) {
            return null;
        }

        $lineage['ancestors'] = array_values(array_filter($lineage['ancestors'], function ($student) use ($limit) {
            return (int) $student['line_level'] <= $limit;
        }));
        $lineage['descendants'] = array_values(array_filter($lineage['descendants'], function ($student) use ($limit) {
            return (int) $student['line_level'] <= $limit;
        }));

        return $lineage;
    }

    public function getLineagePage($id, $direction, $limit, $offset)
    {
        $lineage = $this->getLineage($id);

        if (!$lineage) {
            return null;
        }

        $items = $direction === 'up' ? $lineage['ancestors'] : $lineage['descendants'];

        return [
            'student' => $lineage['student'],
            'items' => array_slice($items, $offset, $limit),
            'total' => count($items),
        ];
    }

    public function isInLineage($ownerStudentId, $targetStudentId)
    {
        if ((int) $ownerStudentId === (int) $targetStudentId) {
            return true;
        }

        $lineage = $this->getLineage($ownerStudentId);

        if (!$lineage) {
            return false;
        }

        foreach (array_merge($lineage['ancestors'], $lineage['descendants']) as $student) {
            if ((int) $student['id'] === (int) $targetStudentId) {
                return true;
            }
        }

        return false;
    }

    public function create($data, $createUser = true)
    {
        $data = $this->normalize($data);
        $parentId = $this->resolveParentId($data['parent_student_id']);

        if ($this->hasProfileImageColumn()) {
            $stmt = $this->conn->prepare(
                'INSERT INTO students
                 (student_code, first_name, last_name, nickname, generation, faculty, major, phone, facebook, instagram, line_id_contact, parent_student_id, profile_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssssissssssis',
                $data['student_code'],
                $data['first_name'],
                $data['last_name'],
                $data['nickname'],
                $data['generation'],
                $data['faculty'],
                $data['major'],
                $data['phone'],
                $data['facebook'],
                $data['instagram'],
                $data['line_id_contact'],
                $parentId,
                $data['profile_image']
            );
        } else {
            $stmt = $this->conn->prepare(
                'INSERT INTO students
                 (student_code, first_name, last_name, nickname, generation, faculty, major, phone, facebook, instagram, line_id_contact, parent_student_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssssissssssi',
                $data['student_code'],
                $data['first_name'],
                $data['last_name'],
                $data['nickname'],
                $data['generation'],
                $data['faculty'],
                $data['major'],
                $data['phone'],
                $data['facebook'],
                $data['instagram'],
                $data['line_id_contact'],
                $parentId
            );
        }
        $stmt->execute();
        $studentId = $this->conn->insert_id;
        $stmt->close();

        if ($createUser) {
            $this->studentUser->createDefaultUser($studentId, $data['student_code'], $data['phone']);
        }

        return $studentId;
    }

    public function update($id, $data)
    {
        $data = $this->normalize($data);
        $parentId = $this->resolveParentId($data['parent_student_id'], $id);

        if ($this->hasProfileImageColumn()) {
            $stmt = $this->conn->prepare(
                'UPDATE students
                 SET student_code = ?, first_name = ?, last_name = ?, nickname = ?, generation = ?,
                     faculty = ?, major = ?, phone = ?, facebook = ?, instagram = ?, line_id_contact = ?, parent_student_id = ?, profile_image = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssssissssssisi',
                $data['student_code'],
                $data['first_name'],
                $data['last_name'],
                $data['nickname'],
                $data['generation'],
                $data['faculty'],
                $data['major'],
                $data['phone'],
                $data['facebook'],
                $data['instagram'],
                $data['line_id_contact'],
                $parentId,
                $data['profile_image'],
                $id
            );
        } else {
            $stmt = $this->conn->prepare(
                'UPDATE students
                 SET student_code = ?, first_name = ?, last_name = ?, nickname = ?, generation = ?,
                     faculty = ?, major = ?, phone = ?, facebook = ?, instagram = ?, line_id_contact = ?, parent_student_id = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssssissssssii',
                $data['student_code'],
                $data['first_name'],
                $data['last_name'],
                $data['nickname'],
                $data['generation'],
                $data['faculty'],
                $data['major'],
                $data['phone'],
                $data['facebook'],
                $data['instagram'],
                $data['line_id_contact'],
                $parentId,
                $id
            );
        }
        $stmt->execute();
        $stmt->close();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM students WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    public function importCsv($filePath)
    {
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new RuntimeException('ไม่สามารถอ่านไฟล์ CSV ได้');
        }

        $headers = fgetcsv($handle);
        $imported = 0;
        $updated = 0;
        $createdUsers = 0;
        $rowsToLink = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = $this->mapCsvRow($headers, $row);

            if (trim($data['student_code']) === '' || trim($data['first_name']) === '' || trim($data['last_name']) === '') {
                continue;
            }

            $rowsToLink[] = $data;
            $existing = $this->findByCode($data['student_code']);

            if ($existing) {
                $this->update((int) $existing['id'], $data);
                if ($this->studentUser->createDefaultUser((int) $existing['id'], $data['student_code'], $data['phone'])) {
                    $createdUsers++;
                }
                $updated++;
            } else {
                $studentId = $this->create($data, false);
                if ($this->studentUser->createDefaultUser($studentId, $data['student_code'], $data['phone'])) {
                    $createdUsers++;
                }
                $imported++;
            }
        }

        fclose($handle);

        foreach ($rowsToLink as $data) {
            if (trim($data['parent_student_id']) === '') {
                continue;
            }

            $student = $this->findByCode($data['student_code']);

            if ($student) {
                $this->update((int) $student['id'], $data);
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'created_users' => $createdUsers,
        ];
    }

    private function normalize($data)
    {
        return [
            'student_code' => isset($data['student_code']) ? trim($data['student_code']) : '',
            'first_name' => isset($data['first_name']) ? trim($data['first_name']) : '',
            'last_name' => isset($data['last_name']) ? trim($data['last_name']) : '',
            'nickname' => isset($data['nickname']) ? trim($data['nickname']) : '',
            'generation' => isset($data['generation']) ? (int) $data['generation'] : 0,
            'faculty' => isset($data['faculty']) ? trim($data['faculty']) : '',
            'major' => isset($data['major']) ? trim($data['major']) : '',
            'phone' => isset($data['phone']) ? trim($data['phone']) : '',
            'facebook' => isset($data['facebook']) ? trim($data['facebook']) : '',
            'instagram' => isset($data['instagram']) ? trim($data['instagram']) : '',
            'line_id_contact' => isset($data['line_id_contact']) ? trim($data['line_id_contact']) : '',
            'parent_student_id' => isset($data['parent_student_id']) ? trim($data['parent_student_id']) : '',
            'profile_image' => isset($data['profile_image']) ? trim($data['profile_image']) : '',
        ];
    }

    private function resolveParentId($parentValue, $currentStudentId = null)
    {
        $parentValue = trim((string) $parentValue);

        if ($parentValue === '') {
            return null;
        }

        $parent = $this->findByCode($parentValue);

        if (!$parent && ctype_digit($parentValue)) {
            $parent = $this->find((int) $parentValue);
        }

        if (!$parent) {
            return null;
        }

        if ($currentStudentId !== null && (int) $parent['id'] === (int) $currentStudentId) {
            return null;
        }

        return (int) $parent['id'];
    }

    private function findWithParent($id)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, p.student_code AS parent_student_code,
                    p.first_name AS parent_first_name, p.last_name AS parent_last_name
             FROM students s
             LEFT JOIN students p ON s.parent_student_id = p.id
             WHERE s.id = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $student;
    }

    private function getAncestors($student)
    {
        $ancestors = [];
        $visited = [];
        $parentId = isset($student['parent_student_id']) ? (int) $student['parent_student_id'] : 0;
        $level = 1;

        while ($parentId > 0 && !isset($visited[$parentId]) && $level <= 100) {
            $visited[$parentId] = true;
            $parent = $this->findWithParent($parentId);

            if (!$parent) {
                break;
            }

            $parent['line_level'] = $level;
            $ancestors[] = $parent;
            $parentId = isset($parent['parent_student_id']) ? (int) $parent['parent_student_id'] : 0;
            $level++;
        }

        return $ancestors;
    }

    private function getDescendants($studentId, $level = 1, $visited = [])
    {
        if ($level > 100 || isset($visited[$studentId])) {
            return [];
        }

        $visited[$studentId] = true;
        $children = $this->getChildren($studentId);
        $descendants = [];

        foreach ($children as $child) {
            $child['line_level'] = $level;
            $descendants[] = $child;

            $childDescendants = $this->getDescendants((int) $child['id'], $level + 1, $visited);
            foreach ($childDescendants as $descendant) {
                $descendants[] = $descendant;
            }
        }

        return $descendants;
    }

    private function getChildren($parentStudentId)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, p.student_code AS parent_student_code
             FROM students s
             LEFT JOIN students p ON s.parent_student_id = p.id
             WHERE s.parent_student_id = ?
             ORDER BY s.generation ASC, s.student_code ASC'
        );
        $stmt->bind_param('i', $parentStudentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $children = [];

        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }

        $stmt->close();
        return $children;
    }

    private function mapCsvRow($headers, $row)
    {
        $allowed = [
            'student_code', 'first_name', 'last_name', 'nickname', 'generation',
            'faculty', 'major', 'phone', 'facebook', 'instagram',
            'line_id_contact', 'parent_student_id', 'profile_image',
        ];
        $data = array_fill_keys($allowed, '');

        foreach ($headers as $index => $header) {
            $key = trim($header);
            if (in_array($key, $allowed, true)) {
                $data[$key] = isset($row[$index]) ? $row[$index] : '';
            } elseif ($key === 'parent_student_code') {
                $data['parent_student_id'] = isset($row[$index]) ? $row[$index] : '';
            }
        }

        return $data;
    }

    private function hasProfileImageColumn()
    {
        if ($this->hasProfileImageColumn !== null) {
            return $this->hasProfileImageColumn;
        }

        $result = $this->conn->query("SHOW COLUMNS FROM students LIKE 'profile_image'");
        $this->hasProfileImageColumn = $result && $result->num_rows > 0;

        return $this->hasProfileImageColumn;
    }
}
