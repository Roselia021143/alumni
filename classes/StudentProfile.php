<?php

class StudentProfile
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getPortfolio($studentId)
    {
        return [
            'skills' => $this->getItems('student_skills', $studentId, 'sort_order ASC, skill_name ASC'),
            'projects' => $this->getItems('student_projects', $studentId, 'is_featured DESC, sort_order ASC, created_at DESC'),
            'experiences' => $this->getItems('student_experiences', $studentId, 'is_current DESC, started_at DESC, sort_order ASC'),
            'activities' => $this->getItems('student_activities', $studentId, 'activity_date DESC, sort_order ASC'),
        ];
    }

    public function updateOverview($studentId, $data)
    {
        $educationStatus = $this->enumValue($data, 'education_status', ['unspecified', 'studying', 'graduated', 'on_leave']);
        $employmentStatus = $this->enumValue($data, 'employment_status', ['unspecified', 'looking_for_internship', 'looking_for_work', 'employed', 'freelance', 'business_owner', 'not_available']);
        $profileVisibility = $this->enumValue($data, 'profile_visibility', ['private', 'members', 'public'], 'members');
        $currentStudyYear = $this->nullableInt($data, 'current_study_year', 1, 8);
        $expectedGraduationYear = $this->nullableInt($data, 'expected_graduation_year', 2400, 2700);
        $graduationYear = $this->nullableInt($data, 'graduation_year', 2400, 2700);
        $headline = $this->text($data, 'headline', 180);
        $bio = isset($data['bio']) ? trim((string) $data['bio']) : '';
        $currentPosition = $this->text($data, 'current_position', 150);
        $currentCompany = $this->text($data, 'current_company', 180);
        $workLocation = $this->text($data, 'work_location', 180);
        $websiteUrl = $this->url($data, 'website_url');
        $githubUrl = $this->url($data, 'github_url');
        $linkedinUrl = $this->url($data, 'linkedin_url');
        $sectionVisibility = [];

        foreach (['about_visible', 'education_visible', 'employment_visible', 'skills_visible', 'projects_visible', 'experiences_visible', 'activities_visible'] as $field) {
            $sectionVisibility[$field] = isset($data[$field]) ? 1 : 0;
        }

        $stmt = $this->conn->prepare(
            'UPDATE students SET
                education_status = ?, current_study_year = ?, expected_graduation_year = ?, graduation_year = ?,
                headline = ?, bio = ?, employment_status = ?, current_position = ?, current_company = ?, work_location = ?,
                website_url = ?, github_url = ?, linkedin_url = ?, profile_visibility = ?,
                about_visible = ?, education_visible = ?, employment_visible = ?, skills_visible = ?,
                projects_visible = ?, experiences_visible = ?, activities_visible = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'siiissssssssssiiiiiiii',
            $educationStatus,
            $currentStudyYear,
            $expectedGraduationYear,
            $graduationYear,
            $headline,
            $bio,
            $employmentStatus,
            $currentPosition,
            $currentCompany,
            $workLocation,
            $websiteUrl,
            $githubUrl,
            $linkedinUrl,
            $profileVisibility,
            $sectionVisibility['about_visible'],
            $sectionVisibility['education_visible'],
            $sectionVisibility['employment_visible'],
            $sectionVisibility['skills_visible'],
            $sectionVisibility['projects_visible'],
            $sectionVisibility['experiences_visible'],
            $sectionVisibility['activities_visible'],
            $studentId
        );
        $stmt->execute();
        $stmt->close();

        $this->replaceSkills($studentId, isset($data['skills']) ? $data['skills'] : '');
    }

    public function saveProject($studentId, $data)
    {
        $title = $this->required($data, 'title', 'กรุณากรอกชื่อผลงาน', 180);
        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $technologies = $this->text($data, 'technologies', 500);
        $projectUrl = $this->url($data, 'project_url');
        $repositoryUrl = $this->url($data, 'repository_url');
        $isFeatured = isset($data['is_featured']) ? 1 : 0;
        $stmt = $this->conn->prepare('INSERT INTO student_projects (student_id, title, description, technologies, project_url, repository_url, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssi', $studentId, $title, $description, $technologies, $projectUrl, $repositoryUrl, $isFeatured);
        $stmt->execute();
        $stmt->close();
    }

    public function saveExperience($studentId, $data)
    {
        $position = $this->required($data, 'position', 'กรุณากรอกตำแหน่ง', 150);
        $organization = $this->required($data, 'organization', 'กรุณากรอกชื่อองค์กร', 180);
        $employmentType = $this->text($data, 'employment_type', 50);
        $location = $this->text($data, 'location', 180);
        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $startedAt = $this->date($data, 'started_at');
        $endedAt = $this->date($data, 'ended_at');
        $isCurrent = isset($data['is_current']) ? 1 : 0;
        $stmt = $this->conn->prepare('INSERT INTO student_experiences (student_id, position, organization, employment_type, location, description, started_at, ended_at, is_current) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssssi', $studentId, $position, $organization, $employmentType, $location, $description, $startedAt, $endedAt, $isCurrent);
        $stmt->execute();
        $stmt->close();
    }

    public function saveActivity($studentId, $data)
    {
        $activityType = $this->text($data, 'activity_type', 50);
        $title = $this->required($data, 'title', 'กรุณากรอกชื่อกิจกรรมหรือความสำเร็จ', 180);
        $organization = $this->text($data, 'organization', 180);
        $roleName = $this->text($data, 'role_name', 150);
        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $activityDate = $this->date($data, 'activity_date');
        $referenceUrl = $this->url($data, 'reference_url');
        $stmt = $this->conn->prepare('INSERT INTO student_activities (student_id, activity_type, title, organization, role_name, description, activity_date, reference_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssss', $studentId, $activityType, $title, $organization, $roleName, $description, $activityDate, $referenceUrl);
        $stmt->execute();
        $stmt->close();
    }

    public function deleteItem($studentId, $type, $itemId)
    {
        $tables = ['project' => 'student_projects', 'experience' => 'student_experiences', 'activity' => 'student_activities'];

        if (!isset($tables[$type])) {
            throw new RuntimeException('ไม่พบประเภทรายการที่ต้องการลบ');
        }

        $stmt = $this->conn->prepare('DELETE FROM ' . $tables[$type] . ' WHERE id = ? AND student_id = ?');
        $stmt->bind_param('ii', $itemId, $studentId);
        $stmt->execute();
        $stmt->close();
    }

    private function replaceSkills($studentId, $skillsInput)
    {
        $skills = preg_split('/[,\r\n]+/u', (string) $skillsInput);
        $skills = array_values(array_unique(array_filter(array_map('trim', $skills))));
        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare('DELETE FROM student_skills WHERE student_id = ?');
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare('INSERT INTO student_skills (student_id, skill_name, sort_order) VALUES (?, ?, ?)');
            foreach (array_slice($skills, 0, 30) as $index => $skill) {
                $skill = mb_substr($skill, 0, 100, 'UTF-8');
                $sortOrder = $index + 1;
                $stmt->bind_param('isi', $studentId, $skill, $sortOrder);
                $stmt->execute();
            }
            $stmt->close();
            $this->conn->commit();
        } catch (Throwable $exception) {
            $this->conn->rollback();
            throw $exception;
        }
    }

    private function getItems($table, $studentId, $orderBy)
    {
        $stmt = $this->conn->prepare('SELECT * FROM ' . $table . ' WHERE student_id = ? ORDER BY ' . $orderBy);
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }

    private function enumValue($data, $field, $allowed, $default = 'unspecified')
    {
        $value = isset($data[$field]) ? (string) $data[$field] : $default;
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function nullableInt($data, $field, $min, $max)
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return null;
        }
        $value = (int) $data[$field];
        return $value >= $min && $value <= $max ? $value : null;
    }

    private function text($data, $field, $maxLength)
    {
        return mb_substr(isset($data[$field]) ? trim((string) $data[$field]) : '', 0, $maxLength, 'UTF-8');
    }

    private function required($data, $field, $message, $maxLength)
    {
        $value = $this->text($data, $field, $maxLength);
        if ($value === '') {
            throw new RuntimeException($message);
        }
        return $value;
    }

    private function url($data, $field)
    {
        $value = $this->text($data, $field, 500);
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('กรุณากรอก URL ให้ถูกต้อง');
        }
        return $value;
    }

    private function date($data, $field)
    {
        $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
        if ($value === '') {
            return null;
        }
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
