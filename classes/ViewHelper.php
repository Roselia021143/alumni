<?php

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function studentPhotoUrl($student)
{
    if (!isset($student['profile_image'])) {
        return '';
    }

    $profileImage = trim((string) $student['profile_image']);

    if ($profileImage === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $profileImage)) {
        return $profileImage;
    }

    if (strpos($profileImage, '/') === 0) {
        return $profileImage;
    }

    return '../' . ltrim($profileImage, '/');
}

function renderStudentAvatar($student, $className = 'h-12 w-12')
{
    $photoUrl = studentPhotoUrl($student);
    $name = trim((isset($student['first_name']) ? $student['first_name'] : '') . ' ' . (isset($student['last_name']) ? $student['last_name'] : ''));
    $label = $name !== '' ? $name : 'Student';

    if ($photoUrl !== '') {
        return '<img src="' . h($photoUrl) . '" alt="' . h($label) . '" class="' . h($className) . ' rounded-full object-cover ring-1 ring-slate-200">';
    }

    return '<div class="' . h($className) . ' flex shrink-0 items-center justify-center rounded-full bg-slate-50 text-teal-700 ring-1 ring-slate-200" aria-label="' . h($label) . '">' .
        '<svg xmlns="http://www.w3.org/2000/svg" class="h-1/2 w-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' .
        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z" />' .
        '<path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0116 0" />' .
        '</svg>' .
        '</div>';
}
