<?php

require_once __DIR__ . '/StudentUser.php';

class Student
{
    private $conn;
    private $studentUser;
    private $hasProfileImageColumn;
    private $hasEmailVisibleColumn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->studentUser = new StudentUser($conn);
    }

    public function all($keyword = '', $limit = null, $offset = 0, $filters = [])
    {
        $keyword = trim($keyword);
        $generation = isset($filters['generation']) ? trim((string) $filters['generation']) : '';
        $faculty = isset($filters['faculty']) ? trim((string) $filters['faculty']) : '';
        $major = isset($filters['major']) ? trim((string) $filters['major']) : '';
        $usePagination = $limit !== null;
        $limit = (int) $limit;
        $offset = (int) $offset;
        $search = '%' . $keyword . '%';
        $sql = 'SELECT s.*, p.student_code AS parent_student_code
             FROM students s
             LEFT JOIN students p ON s.parent_student_id = p.id
             WHERE (? = "" OR s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, " ", s.last_name) LIKE ?)
               AND (? = "" OR CAST(s.generation AS CHAR) = ?)
               AND (? = "" OR s.faculty = ?)
               AND (? = "" OR s.major = ?)
             ORDER BY s.student_code ASC';

        if ($usePagination) {
            $sql .= ' LIMIT ? OFFSET ?';
        }

        $stmt = $this->conn->prepare($sql);

        if ($usePagination) {
            $stmt->bind_param('sssssssssssii', $keyword, $search, $search, $search, $search, $generation, $generation, $faculty, $faculty, $major, $major, $limit, $offset);
        } else {
            $stmt->bind_param('sssssssssss', $keyword, $search, $search, $search, $search, $generation, $generation, $faculty, $faculty, $major, $major);
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

    public function countAll($keyword = '', $filters = [])
    {
        $keyword = trim($keyword);
        $generation = isset($filters['generation']) ? trim((string) $filters['generation']) : '';
        $faculty = isset($filters['faculty']) ? trim((string) $filters['faculty']) : '';
        $major = isset($filters['major']) ? trim((string) $filters['major']) : '';
        $search = '%' . $keyword . '%';
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS total
             FROM students s
             WHERE (? = "" OR s.student_code LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, " ", s.last_name) LIKE ?)
               AND (? = "" OR CAST(s.generation AS CHAR) = ?)
               AND (? = "" OR s.faculty = ?)
               AND (? = "" OR s.major = ?)'
        );
        $stmt->bind_param('sssssssssss', $keyword, $search, $search, $search, $search, $generation, $generation, $faculty, $faculty, $major, $major);

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? (int) $row['total'] : 0;
    }

    public function filterOptions()
    {
        $options = [
            'generations' => [],
            'faculties' => [],
            'majors' => [],
        ];
        $queries = [
            'generations' => 'SELECT DISTINCT generation AS value FROM students WHERE generation IS NOT NULL AND generation <> "" ORDER BY generation DESC',
            'faculties' => 'SELECT DISTINCT faculty AS value FROM students WHERE faculty IS NOT NULL AND faculty <> "" ORDER BY faculty ASC',
            'majors' => 'SELECT DISTINCT major AS value FROM students WHERE major IS NOT NULL AND major <> "" ORDER BY major ASC',
        ];

        foreach ($queries as $key => $sql) {
            $result = $this->conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                $options[$key][] = $row['value'];
            }
        }

        return $options;
    }

    public function find($id)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, su.email
             FROM students s
             LEFT JOIN student_users su ON su.student_id = s.id
             WHERE s.id = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $student;
    }

    public function findByCode($studentCode)
    {
        $stmt = $this->conn->prepare(
            'SELECT s.*, su.email
             FROM students s
             LEFT JOIN student_users su ON su.student_id = s.id
             WHERE s.student_code = ?
             LIMIT 1'
        );
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

    public function createRegistration($data, $password)
    {
        $studentCode = isset($data['student_code']) ? trim($data['student_code']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';

        if ($studentCode === '') {
            throw new RuntimeException('กรุณากรอกรหัสนักศึกษา');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('กรุณากรอก Email ให้ถูกต้อง');
        }

        if ($this->findByCode($studentCode)) {
            throw new RuntimeException('รหัสนักศึกษานี้มีอยู่ในระบบแล้ว');
        }

        if ($this->studentUser->emailExists($email)) {
            throw new RuntimeException('Email นี้ถูกใช้สมัครแล้ว');
        }

        $registrationData = [
            'student_code' => $studentCode,
            'first_name' => '',
            'last_name' => '',
            'nickname' => '',
            'generation' => $this->generationFromStudentCode($studentCode),
            'faculty' => '',
            'major' => '',
            'phone' => '',
            'facebook' => '',
            'instagram' => '',
            'line_id_contact' => '',
            'parent_student_id' => '',
            'profile_image' => '',
        ];

        $studentId = $this->create($registrationData, false);

        if (!$this->studentUser->createUser($studentId, $studentCode, $password, $email)) {
            throw new RuntimeException('ไม่สามารถสร้างบัญชีผู้ใช้ได้');
        }

        return $studentId;
    }

    public function updateOwnProfile($id, $data)
    {
        $data = $this->normalize($data);

        foreach (['first_name', 'last_name', 'faculty', 'major'] as $requiredField) {
            if ($data[$requiredField] === '') {
                throw new RuntimeException('กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน');
            }
        }

        $stmt = $this->conn->prepare(
            'UPDATE students
             SET first_name = ?, last_name = ?, nickname = ?, faculty = ?, major = ?,
                 phone = ?, facebook = ?, instagram = ?, line_id_contact = ?, profile_image = ?,
                 student_code_visible = ?, generation_visible = ?, phone_visible = ?,
                 facebook_visible = ?, instagram_visible = ?, line_id_contact_visible = ?, profile_image_visible = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'ssssssssssiiiiiiii',
            $data['first_name'],
            $data['last_name'],
            $data['nickname'],
            $data['faculty'],
            $data['major'],
            $data['phone'],
            $data['facebook'],
            $data['instagram'],
            $data['line_id_contact'],
            $data['profile_image'],
            $data['student_code_visible'],
            $data['generation_visible'],
            $data['phone_visible'],
            $data['facebook_visible'],
            $data['instagram_visible'],
            $data['line_id_contact_visible'],
            $data['profile_image_visible'],
            $id
        );
        $stmt->execute();
        $stmt->close();

        if ($this->hasEmailVisibleColumn()) {
            $stmt = $this->conn->prepare('UPDATE students SET email_visible = ? WHERE id = ?');
            $stmt->bind_param('ii', $data['email_visible'], $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function admissionYearFromCode($studentCode)
    {
        $prefix = substr(trim((string) $studentCode), 0, 2);

        if (!ctype_digit($prefix)) {
            return 0;
        }

        return 2500 + (int) $prefix;
    }

    private function generationFromStudentCode($studentCode)
    {
        $prefix = substr(trim((string) $studentCode), 0, 2);

        if (!ctype_digit($prefix)) {
            return 0;
        }

        return (int) $prefix;
    }

    private function assertDifferentGeneration($studentCode, $targetStudentCode)
    {
        $studentGeneration = $this->generationFromStudentCode($studentCode);
        $targetGeneration = $this->generationFromStudentCode($targetStudentCode);

        if ($studentGeneration > 0 && $targetGeneration > 0 && $studentGeneration === $targetGeneration) {
            throw new RuntimeException('ไม่สามารถผูกพี่รหัสหรือน้องรหัสที่อยู่ปีการศึกษาเดียวกันได้');
        }
    }

    public function studentYearLevel($studentCode, $currentAcademicYear)
    {
        $admissionYear = $this->admissionYearFromCode($studentCode);

        if ($admissionYear <= 0) {
            return 0;
        }

        return max(1, (int) $currentAcademicYear - $admissionYear + 1);
    }

    public function canManageChildCode($studentCode, $currentAcademicYear)
    {
        return $this->studentYearLevel($studentCode, $currentAcademicYear) >= 2;
    }

    public function linkParentByCode($studentId, $parentStudentCode)
    {
        $student = $this->find($studentId);
        $parent = $this->findByCode($parentStudentCode);

        if (!$student || !$parent || (int) $student['id'] === (int) $parent['id']) {
            throw new RuntimeException('ไม่พบข้อมูลพี่รหัส หรือรหัสไม่ถูกต้อง');
        }

        if (!empty($student['parent_student_id'])) {
            throw new RuntimeException('คุณมีพี่รหัสอยู่แล้ว หากต้องการเปลี่ยนพี่รหัสกรุณาติดต่อผู้ดูแลระบบ');
        }

        $this->assertDifferentGeneration($student['student_code'], $parent['student_code']);

        $stmt = $this->conn->prepare('UPDATE students SET parent_student_id = ? WHERE id = ?');
        $parentId = (int) $parent['id'];
        $stmt->bind_param('ii', $parentId, $studentId);
        $stmt->execute();
        $stmt->close();

        $this->recordLineRequest($studentId, $parentStudentCode, 'parent');
    }

    public function linkChildByCode($studentId, $childStudentCode)
    {
        $student = $this->find($studentId);
        $child = $this->findByCode($childStudentCode);

        if (!$student || !$child || (int) $student['id'] === (int) $child['id']) {
            throw new RuntimeException('ไม่พบข้อมูลน้องรหัส หรือรหัสไม่ถูกต้อง');
        }

        $this->assertDifferentGeneration($student['student_code'], $child['student_code']);

        $stmt = $this->conn->prepare('UPDATE students SET parent_student_id = ? WHERE id = ?');
        $stmt->bind_param('ii', $studentId, $child['id']);
        $stmt->execute();
        $stmt->close();

        $this->recordLineRequest($studentId, $childStudentCode, 'child');
    }

    public function adminSetParentByCode($studentId, $parentStudentCode)
    {
        $student = $this->find($studentId);
        $parentStudentCode = trim((string) $parentStudentCode);

        if (!$student) {
            throw new RuntimeException('ไม่พบข้อมูลนักศึกษา');
        }

        if ($parentStudentCode === '') {
            $this->unlinkParent($studentId);
            return;
        }

        $parent = $this->findByCode($parentStudentCode);

        if (!$parent) {
            throw new RuntimeException('ไม่พบรหัสนักศึกษาของพี่รหัสในระบบ');
        }

        if ((int) $student['id'] === (int) $parent['id']) {
            throw new RuntimeException('ไม่สามารถกำหนดนักศึกษาเป็นพี่รหัสของตัวเองได้');
        }

        $this->assertDifferentGeneration($student['student_code'], $parent['student_code']);

        foreach ($this->getDescendants((int) $student['id']) as $descendant) {
            if ((int) $descendant['id'] === (int) $parent['id']) {
                throw new RuntimeException('ไม่สามารถผูกสายย้อนกลับได้ เนื่องจากจะทำให้เกิดวงจรในสายรหัส');
            }
        }

        $parentId = (int) $parent['id'];
        $stmt = $this->conn->prepare('UPDATE students SET parent_student_id = ? WHERE id = ?');
        $stmt->bind_param('ii', $parentId, $studentId);
        $stmt->execute();
        $stmt->close();
    }

    public function unlinkParent($studentId)
    {
        $studentId = (int) $studentId;

        if ($studentId <= 0 || !$this->find($studentId)) {
            throw new RuntimeException('ไม่พบข้อมูลนักศึกษา');
        }

        $stmt = $this->conn->prepare('UPDATE students SET parent_student_id = NULL WHERE id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stmt->close();
    }

    public function getTree($id, $upDepth = null, $downDepth = null)
    {
        $lineage = $this->getLineage($id);

        if (!$lineage) {
            return null;
        }

        $ancestors = $lineage['ancestors'];
        $descendants = $lineage['descendants'];

        if ($upDepth !== null) {
            $ancestors = array_values(array_filter($ancestors, function ($student) use ($upDepth) {
                return (int) $student['line_level'] <= (int) $upDepth;
            }));
        }

        if ($downDepth !== null) {
            $descendants = array_values(array_filter($descendants, function ($student) use ($downDepth) {
                return (int) $student['line_level'] <= (int) $downDepth;
            }));
        }

        $root = $lineage['student'];
        $root['line_level'] = 0;

        return [
            'ancestors' => array_reverse($ancestors),
            'root' => $root,
            'children_by_parent' => $this->groupByParent($descendants),
        ];
    }

    public function dashboardStats()
    {
        $totalStudents = $this->countAll('');
        $roots = $this->lineRoots();
        $lines = [];

        foreach ($roots as $root) {
            $lineage = $this->getLineage((int) $root['id']);
            $count = $lineage ? count($lineage['descendants']) + 1 : 1;
            $lines[] = [
                'student' => $root,
                'count' => $count,
                'percent' => $totalStudents > 0 ? round(($count / $totalStudents) * 100, 2) : 0,
            ];
        }

        return [
            'total_students' => $totalStudents,
            'total_lines' => count($lines),
            'lines' => $lines,
        ];
    }

    public function publicStats()
    {
        $result = $this->conn->query(
            'SELECT
                COUNT(*) AS total_students,
                SUM(CASE WHEN parent_student_id IS NULL THEN 1 ELSE 0 END) AS total_lines,
                SUM(CASE WHEN parent_student_id IS NOT NULL THEN 1 ELSE 0 END) AS total_relationships
             FROM students'
        );
        $stats = $result->fetch_assoc();

        return [
            'total_students' => (int) ($stats['total_students'] ?? 0),
            'total_lines' => (int) ($stats['total_lines'] ?? 0),
            'total_relationships' => (int) ($stats['total_relationships'] ?? 0),
        ];
    }

    public function publicProgramForest(array $keywords)
    {
        $result = $this->conn->query(
            'SELECT id, first_name, last_name, nickname, generation, major, parent_student_id
             FROM students
             WHERE major IS NOT NULL AND major <> ""
             ORDER BY generation ASC, student_code ASC'
        );
        $students = [];

        while ($student = $result->fetch_assoc()) {
            $major = trim((string) $student['major']);
            $matches = false;

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && mb_stripos($major, $keyword, 0, 'UTF-8') !== false) {
                    $matches = true;
                    break;
                }
            }

            if ($matches) {
                $student['id'] = (int) $student['id'];
                $student['parent_student_id'] = $student['parent_student_id'] === null
                    ? null
                    : (int) $student['parent_student_id'];
                $students[$student['id']] = $student;
            }
        }

        $roots = [];
        $childrenByParent = [];

        foreach ($students as $student) {
            $parentId = $student['parent_student_id'];

            if ($parentId === null || !isset($students[$parentId]) || $parentId === $student['id']) {
                $roots[] = $student;
                continue;
            }

            if (!isset($childrenByParent[$parentId])) {
                $childrenByParent[$parentId] = [];
            }

            $childrenByParent[$parentId][] = $student;
        }

        return [
            'roots' => $roots,
            'children_by_parent' => $childrenByParent,
            'total_students' => count($students),
            'total_lines' => count($roots),
        ];
    }

    public function academicMajorStats($currentAcademicYear)
    {
        $majors = [
            'computer_science' => [
                'label' => 'วิทยาการคอมพิวเตอร์',
                'keywords' => ['วิทยาการคอมพิวเตอร์'],
            ],
            'information_technology' => [
                'label' => 'เทคโนโลยีสารสนเทศ',
                'keywords' => ['เทคโนโลยีสารสนเทศ'],
            ],
            'multimedia' => [
                'label' => 'เทคโนโลยีมัลติมีเดียและแอนิเมชัน',
                'keywords' => ['มัลติมีเดีย', 'มัลติมิเดีย', 'แอนิเมชัน', 'แอนิเมชั่น'],
            ],
        ];

        foreach ($majors as &$major) {
            $major['total'] = 0;
            $major['total_lines'] = 0;
            $major['years'] = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            $major['other_years'] = 0;
        }
        unset($major);

        $result = $this->conn->query(
            'SELECT major, generation, parent_student_id
             FROM students
             WHERE major IS NOT NULL AND major <> ""'
        );
        $currentGeneration = ((int) $currentAcademicYear) % 100;

        while ($student = $result->fetch_assoc()) {
            $majorKey = null;
            $studentMajor = trim((string) $student['major']);

            foreach ($majors as $key => $major) {
                foreach ($major['keywords'] as $keyword) {
                    if (mb_stripos($studentMajor, $keyword, 0, 'UTF-8') !== false) {
                        $majorKey = $key;
                        break 2;
                    }
                }
            }

            if ($majorKey === null) {
                continue;
            }

            $majors[$majorKey]['total']++;

            if ($student['parent_student_id'] === null) {
                $majors[$majorKey]['total_lines']++;
            }

            $generation = ((int) $student['generation']) % 100;
            $studyYear = $currentGeneration - $generation + 1;

            if ($studyYear >= 1 && $studyYear <= 4) {
                $majors[$majorKey]['years'][$studyYear]++;
            } else {
                $majors[$majorKey]['other_years']++;
            }
        }

        foreach ($majors as &$major) {
            $major['percentages'] = [];

            foreach ($major['years'] as $year => $count) {
                $major['percentages'][$year] = $major['total'] > 0
                    ? round(($count / $major['total']) * 100, 1)
                    : 0;
            }

            unset($major['keywords']);
        }
        unset($major);

        return [
            'majors' => $majors,
            'total_students' => array_sum(array_column($majors, 'total')),
            'total_lines' => array_sum(array_column($majors, 'total_lines')),
            'current_academic_year' => (int) $currentAcademicYear,
        ];
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
        $skippedDuplicates = 0;
        $rowsToLink = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = $this->mapCsvRow($headers, $row);

            if (trim($data['student_code']) === '' || trim($data['first_name']) === '' || trim($data['last_name']) === '') {
                continue;
            }

            $existing = $this->findByCode($data['student_code']);

            if ($existing) {
                $skippedDuplicates++;
                continue;
            } else {
                $rowsToLink[] = $data;
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
            'skipped_duplicates' => $skippedDuplicates,
        ];
    }

    private function normalize($data)
    {
        $phone = isset($data['phone']) ? preg_replace('/\D+/', '', trim((string) $data['phone'])) : '';

        return [
            'student_code' => isset($data['student_code']) ? trim($data['student_code']) : '',
            'first_name' => isset($data['first_name']) ? trim($data['first_name']) : '',
            'last_name' => isset($data['last_name']) ? trim($data['last_name']) : '',
            'nickname' => isset($data['nickname']) ? trim($data['nickname']) : '',
            'generation' => isset($data['generation']) ? (int) $data['generation'] : 0,
            'faculty' => isset($data['faculty']) ? trim($data['faculty']) : '',
            'major' => isset($data['major']) ? trim($data['major']) : '',
            'phone' => $phone,
            'facebook' => isset($data['facebook']) ? trim($data['facebook']) : '',
            'instagram' => isset($data['instagram']) ? trim($data['instagram']) : '',
            'line_id_contact' => isset($data['line_id_contact']) ? trim($data['line_id_contact']) : '',
            'parent_student_id' => isset($data['parent_student_id']) ? trim($data['parent_student_id']) : '',
            'profile_image' => isset($data['profile_image']) ? trim($data['profile_image']) : '',
            'student_code_visible' => isset($data['student_code_visible']) ? 1 : 0,
            'generation_visible' => isset($data['generation_visible']) ? 1 : 0,
            'phone_visible' => isset($data['phone_visible']) ? 1 : 0,
            'email_visible' => isset($data['email_visible']) ? 1 : 0,
            'facebook_visible' => isset($data['facebook_visible']) ? 1 : 0,
            'instagram_visible' => isset($data['instagram_visible']) ? 1 : 0,
            'line_id_contact_visible' => isset($data['line_id_contact_visible']) ? 1 : 0,
            'profile_image_visible' => isset($data['profile_image_visible']) ? 1 : 0,
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
            'SELECT s.*, su.email, p.student_code AS parent_student_code,
                    p.first_name AS parent_first_name, p.last_name AS parent_last_name
             FROM students s
             LEFT JOIN student_users su ON su.student_id = s.id
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
            'SELECT s.*, su.email, p.student_code AS parent_student_code
             FROM students s
             LEFT JOIN student_users su ON su.student_id = s.id
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

    private function groupByParent($students)
    {
        $childrenByParent = [];

        foreach ($students as $student) {
            $parentId = isset($student['parent_student_id']) ? (int) $student['parent_student_id'] : 0;

            if (!isset($childrenByParent[$parentId])) {
                $childrenByParent[$parentId] = [];
            }

            $childrenByParent[$parentId][] = $student;
        }

        return $childrenByParent;
    }

    private function lineRoots()
    {
        $result = $this->conn->query(
            'SELECT *
             FROM students
             WHERE parent_student_id IS NULL
             ORDER BY generation ASC, student_code ASC'
        );
        $roots = [];

        while ($row = $result->fetch_assoc()) {
            $roots[] = $row;
        }

        return $roots;
    }

    private function recordLineRequest($requesterStudentId, $targetStudentCode, $direction)
    {
        $exists = $this->conn->query("SHOW TABLES LIKE 'student_line_requests'");

        if (!$exists || $exists->num_rows === 0) {
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO student_line_requests (requester_student_id, target_student_code, direction, status)
             VALUES (?, ?, ?, "approved")'
        );
        $stmt->bind_param('iss', $requesterStudentId, $targetStudentCode, $direction);
        $stmt->execute();
        $stmt->close();
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

    private function hasEmailVisibleColumn()
    {
        if ($this->hasEmailVisibleColumn !== null) {
            return $this->hasEmailVisibleColumn;
        }

        $result = $this->conn->query("SHOW COLUMNS FROM students LIKE 'email_visible'");
        $this->hasEmailVisibleColumn = $result && $result->num_rows > 0;

        return $this->hasEmailVisibleColumn;
    }
}
