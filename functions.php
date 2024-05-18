<?php
session_start();
require(base_dir() . './connect-db.php');

function back()
{
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
function base_url()
{
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    return $base_url;
}

function base_dir()
{
    return str_replace('\\', '/', __DIR__) . '/';
}

function getCurrentPage()
{
    $url = $_SERVER['REQUEST_URI'];
    $startIndex = strpos($url, 'admin/') + strlen('admin/');
    $endIndex = strpos($url, '/', $startIndex);
    $page = substr($url, $startIndex, $endIndex - $startIndex);
    return $page;
}

function getFileExtension($filepath)
{
    // Get the file extension using pathinfo()
    $extension = pathinfo($filepath, PATHINFO_EXTENSION);

    return $extension;
}
function admin_dir()
{
    return __DIR__ . '/admin/';
}
function student_dir()
{
    return __DIR__ . '/student/';
}

function session($var)
{
    return $_SESSION[$var];
}


function setFlashMessage($messageKey, $messageValue)
{
    $_SESSION[$messageKey] = $messageValue;
}

function getFlashMessage($messageKey)
{
    if (isset($_SESSION[$messageKey])) {
        if ($messageKey == 'success') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> ' . $_SESSION[$messageKey] . ' </div>';
        } else {

            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> ' . $_SESSION[$messageKey] . ' </div>';
        }
        unset($_SESSION[$messageKey]); // Remove the flash message from the session
    }
}

// Authenticationg Logins
// Function to login user
function loginUser($username, $password)
{
    global $pdo;

    try {
        // Retrieve user data from the users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? LIMIT 1");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // echo var_dump($user);
        // exit;
        if ($user) {
            // Store user data in session
            $_SESSION['user'] = $user;
            $_SESSION['auth'] = true;

            return true;
        } else {
            // If login data not found in users table, return false
            return false;
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to login student
function loginStudent($academic_number, $password)
{
    global $pdo;

    try {
        // Retrieve student data from the students table
        $stmt = $pdo->prepare("SELECT * FROM students WHERE academic_number = ? AND password = ? LIMIT 1");
        $stmt->execute([$academic_number, $password]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        // echo var_dump($student);
        // die;
        if ($student) {
            // Store student data in session
            $_SESSION['student'] = $student;
            $_SESSION['auth'] = true;
            return true;
        } else {
            // If $academic_number not found in students table, return false
            return false;
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// التأكد من ان المستخدم قام بتستجيل الدخول
function isAuthenticated()
{
    if (isset($_SESSION['auth']) && $_SESSION['auth']) {
        return true;
    }
    return false;
}
// التأكد من ان المستخدم لم يسجل دخول بالفعل ولديه جلسه نشطة
function isNotAuthenticated()
{
    if (isset($_SESSION['auth']) && isset($_SESSION['student'])) {
        return header('Location: ' . base_url() . '/student/index.php');
    }
    if (isset($_SESSION['auth']) && isset($_SESSION['user'])) {
        return header('Location: ' . base_url() . '/admin/lessons/lessons.php');
    }
}
// التأكد من ان المستخدم قام بتستجيل الدخول
function hasAccess($userId)
{
    $user           = getUser($userId);
    if ($user['is_admin']) {
        return true;
    } else {
        $userPrivileges = getUserPrivileges($userId);
        $currentPage    = getCurrentPage();
        if (in_array($currentPage, $userPrivileges)) {
            return true;
        }
    }

    return false;
}


//###########
// Users Privileges
//###########

// Function to store user privileges
function updatePrivileges($userId, $privileges)
{
    global $pdo;

    $currentPrivileges = getUserPrivileges($userId);
    unset($currentPrivileges["id"], $currentPrivileges["user_id"]);
    // الغاء الصلاحيات القديمة
    foreach ($currentPrivileges as $currentprivilege) {
        $stmt1 = $pdo->prepare("UPDATE privileges SET $currentprivilege = FALSE WHERE user_id = ?");
        $stmt1->execute([$userId]);
    }
    try {
        // اضافة الصلاحيات الجديدة
        foreach ($privileges as $privilege) {
            $stmt2 = $pdo->prepare("UPDATE privileges SET $privilege = TRUE WHERE user_id = ?");
            $stmt2->execute([$userId]);
        }

        return true; // Privileges stored successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
//###########
// Users
//###########

// Function to store a new user
function storeUser($name, $username, $password, $is_admin)
{
    global $pdo;

    try {
        // Insert user data into the users table
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $username, $password, $is_admin]);
        $userId = $pdo->lastInsertId();
        if (!$is_admin) {
            // Get the inserted row by username
            $stmt2 = $pdo->prepare("INSERT INTO privileges (user_id) VALUES (?)");
            $stmt2->execute([$userId]);
        }
        return $userId; // Return the inserted row
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}


// Function to update a user's information
function updateUser($userId, $name, $username, $is_admin, $password = null)
{
    global $pdo;

    try {
        // Prepare SQL statement to update user details
        if ($password !== null) {
            // If a new password is provided, update both name, academic number, is_admin, and password
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, is_admin = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $username, $is_admin, $password, $userId]);
        } else {
            // If no new password is provided, update only name, academic number, and is_admin
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, is_admin = ? WHERE id = ?");
            $stmt->execute([$name, $username, $is_admin, $userId]);
        }

        return true; // user updated successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to delete a user with its associated file
function deleteUser($userId)
{
    global $pdo;

    // Retrieve file URL of the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // Delete user record from the database
    $stmt = $pdo->prepare("DELETE FROM privileges WHERE user_id = ?");
    $stmt->execute([$userId]);

    return true; // user deleted successfully
}

function getUser($userId)
{
    global $pdo;

    try {
        // Prepare SQL statement to retrieve all users and their privileges
        $stmt = $pdo->query("SELECT * FROM users  WHERE id = $userId");

        // Fetch all rows as an associative array
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user; // Return the array of users with privileges
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to get all users along with their privileges
function getUsers()
{
    global $pdo;

    try {
        // Prepare SQL statement to retrieve all users and their privileges
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");

        // Fetch all rows as an associative array
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $users; // Return the array of users with privileges
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
// Function to get all users along with their privileges
function getUserPrivileges($userId)
{
    global $pdo;

    // Prepare SQL statement to retrieve lesson details
    $stmt = $pdo->prepare("SELECT * FROM privileges WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $privileges = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($privileges) {
        unset($privileges['id'], $privileges['user_id']);
        foreach ($privileges as $key => $value) {
            if (!$value) {
                unset($privileges[$key]);
            }
        }
        $privileges = array_keys($privileges);
    }
    return $privileges ? $privileges : array(); //

}

function translatePrivilege($name)
{
    $privilegeNames = array(
        'students'  => 'الطلاب',
        'questions' => 'الاسئلة',
        'users'     => 'المستخدمين',
        'excuses'   => 'الأعذار',
        'categories' => 'الأقسام',
        'lessons'   => 'الدروس',
        'uniforms'   => 'الزي',
        'gallery'   => 'المعرض',
        // Add more privileges as needed
    );

    return $privilegeNames[$name] ?? null;
}

function getUserName()
{
    if (isset($_SESSION['userName'])) {
        return session('userName');
    }
    return 'غير محدد';
}
/*############
## Lessons ###
#############*/
// Function to save uploaded files
function saveUploadedFile($file, $destination)
{
    $uploadDir = base_dir() . 'uploads/' . $destination . '/';

    // Check if directory exists, if not create it
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create directory recursively
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    // Generate a unique filename to avoid overwriting existing files
    $fileName = uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    // Move the uploaded file to the specified directory
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $filePath; // Return the file path
    } else {
        return false; // File upload failed
    }
}

// function to get file directory
function getFileDir($filepath)
{
    // Find the position of "uploads/"
    $uploads_pos = strpos($filepath, "/uploads");

    // If "uploads/" is found
    if ($uploads_pos !== false) {
        // Get the path from "uploads/" onwards
        $uploads_path = substr($filepath, $uploads_pos);
        return $uploads_path;
    } else {
        return "حدث خطأ ما!.";
    }
}


// Function to add a new lesson
function addLesson($title, $description, $fileType, $filePath)
{
    global $pdo;

    // Prepare SQL statement to insert lesson details and file details into the database
    $stmt = $pdo->prepare("INSERT INTO lessons (title, description, file_type, file_url) VALUES (?, ?, ?, ?)");

    // Execute the statement
    $stmt->execute([$title, $description, $fileType, $filePath]);

    return true; // Lesson and file added successfully
}


function getLesson($lessonId)
{
    global $pdo;

    // Prepare SQL statement to retrieve lesson details
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    return $lesson ? $lesson : false; //
}
// Function to update lesson details
function updateLesson($lessonId, $title, $description, $fileType, $filePath)
{
    global $pdo;

    try {
        // Initialize SQL statement
        $sql = "UPDATE lessons SET title = :title, description = :description";

        // If file details are provided, add them to the SQL statement
        if ($filePath !== null) {
            $sql .= ", file_name = :file_name, file_type = :file_type, file_url = :file_url";
        }

        // Complete the SQL statement
        $sql .= " WHERE id = :lesson_id";

        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':lesson_id', $lessonId);

        // If file details are provided, bind them to parameters
        if ($fileType !== null && $filePath !== null) {
            $stmt->bindParam(':file_name', basename($filePath));
            $stmt->bindParam(':file_type', $fileType);
            $stmt->bindParam(':file_url', $filePath);
        }

        // Execute the statement
        $success = $stmt->execute();

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to delete a lesson with its associated file
function deleteLesson($lessonId)
{
    global $pdo;

    // Retrieve file URL of the lesson
    $stmt = $pdo->prepare("SELECT file_url FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $fileUrl = $stmt->fetchColumn();

    // Delete lesson record from the database
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);

    // Delete lesson file from the server
    if (file_exists($fileUrl)) {
        unlink($fileUrl); // Delete file
    }

    return true; // Lesson deleted successfully
}

function getLessons()
{
    global $pdo;

    // Execute SQL query to select all lessons
    $stmt = $pdo->query("SELECT * FROM lessons  ORDER BY id DESC");

    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the lessons array
        return $lessons;
    } else {
        // Return false if the query fails
        return array();
    }
}

// Function to download the uploaded file
function downloadFile($fileUrl)
{
    // Check if the file exists
    if (file_exists($fileUrl)) {
        // Set appropriate headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileUrl) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileUrl));
        ob_clean();
        flush();
        readfile($fileUrl);
        exit;
    } else {
        // File not found
        echo "File not found.";
    }
}

function streamPDF($file_path)
{
    // Check if the file exists
    if (file_exists($file_path)) {
        // Set headers for PDF file
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));

        // Read the file and output it to the browser
        readfile($file_path);

        exit; // Stop further execution
    } else {
        // File not found, output error message
        echo "File not found: " . $file_path;
    }
}


/*############
## Students ###
#############*/

// Function to add a new student
function addStudent($name, $academic_number, $phone, $password)
{
    global $pdo;

    // Prepare SQL statement to insert student details and file details into the database
    $stmt = $pdo->prepare("INSERT INTO students (name, academic_number,phone, password ) VALUES (?, ?, ?, ?)");

    // Execute the statement
    $stmt->execute([$name, $academic_number, $phone, $password]);

    return true; // student and file added successfully
}

// Function to delete a student with its associated file
function deleteStudent($studentId)
{
    global $pdo;

    // Delete student record from the database
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$studentId]);

    return true; // student deleted successfully
}

// Function to update a student's information
function updateStudent($studentId, $name, $academicNumber, $phone, $password = null)
{
    global $pdo;

    try {
        // Prepare SQL statement to update student details
        if ($password !== null) {
            // If a new password is provided, update both name, academic number, phone, and password
            $stmt = $pdo->prepare("UPDATE students SET name = ?, academic_number = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $academicNumber, $phone, $password, $studentId]);
        } else {
            // If no new password is provided, update only name, academic number, and phone
            $stmt = $pdo->prepare("UPDATE students SET name = ?, academic_number = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $academicNumber, $phone, $studentId]);
        }

        return true; // Student updated successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function academicNumberExists($academic_number, $except = null)
{
    global $pdo;

    $sql = "SELECT * FROM students WHERE academic_number = ?";
    if ($except != null) {
        $sql .= " AND academic_number <> ?";
        $params = [$academic_number, $except];
    } else {
        $params = [$academic_number];
    }
    // Prepare SQL statement to retrieve student details
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    return $student ? true : false;
}

function getStudent($studentId)
{
    global $pdo;

    // Prepare SQL statement to retrieve student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    return $student ? $student : false;
}

function getStudents()
{
    global $pdo;

    // Execute SQL query to select all students
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");

    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the students array
        return $students;
    } else {
        // Return false if the query fails
        return array();
    }
}


/*############
## Uniform ###
#############*/
function addUniform($description, $filePath)
{
    global $pdo;

    try {
        // Insert data into the database
        $stmt = $pdo->prepare("INSERT INTO uniforms (description, file_url) VALUES (?, ?)");
        $success = $stmt->execute([$description, $filePath]);

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to update uniform details
function updateUniform($uniformId, $description, $filePath)
{
    global $pdo;

    try {
        // Initialize SQL statement
        $sql = "UPDATE uniforms SET description = :description";

        // If file details are provided, add them to the SQL statement
        if ($filePath !== null) {
            $sql .= ", file_url = :file_url";
        }

        // Complete the SQL statement
        $sql .= " WHERE id = :uniform_id";

        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':uniform_id', $uniformId);

        // If file details are provided, bind them to parameters
        if ($filePath !== null) {
            $stmt->bindParam(':file_url', $filePath);
        }
        // Execute the statement
        $success = $stmt->execute();

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}


// Function to delete a uniform with its associated file
function deleteUniform($uniformId)
{
    global $pdo;

    // Retrieve file URL of the uniform
    $stmt = $pdo->prepare("SELECT file_url FROM uniforms WHERE id = ?");
    $stmt->execute([$uniformId]);
    $fileUrl = $stmt->fetchColumn();

    // Delete uniform record from the database
    $stmt = $pdo->prepare("DELETE FROM uniforms WHERE id = ?");
    $stmt->execute([$uniformId]);

    // Delete uniform file from the server
    if (file_exists($fileUrl)) {
        unlink($fileUrl); // Delete file
    }

    return true; // uniform deleted successfully
}

function getUniform($uniformId)
{
    global $pdo;

    // Prepare SQL statement to retrieve uniform details
    $stmt = $pdo->prepare("SELECT * FROM uniforms WHERE id = ?");
    $stmt->execute([$uniformId]);
    $uniform = $stmt->fetch(PDO::FETCH_ASSOC);

    return $uniform ? $uniform : false;
}

function getUniforms()
{
    global $pdo;

    // Execute SQL query to select all uniforms
    $stmt = $pdo->query("SELECT * FROM uniforms");

    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $uniforms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the uniforms array
        return $uniforms;
    } else {
        // Return false if the query fails
        return array();
    }
}

/*############
## Gallery ###
#############*/
function addMedia($title, $description, $file_url)
{
    global $pdo;

    try {
        // Insert data into the database
        $stmt = $pdo->prepare("INSERT INTO gallery (title, description, file_url) VALUES (?, ?, ?)");
        $success = $stmt->execute([$title, $description, $file_url]);

        return $success;
        exit;
        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to update media details
function updateMedia($mediaId, $title, $description, $filePath)
{
    global $pdo;

    try {
        // Initialize SQL statement
        $sql = "UPDATE gallery SET title = :title, description = :description";

        // If file details are provided, add them to the SQL statement
        if ($filePath !== null) {
            $sql .= ", file_url = :file_url";
        }

        // Complete the SQL statement
        $sql .= " WHERE id = :media_id";

        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':media_id', $mediaId);

        // If file details are provided, bind them to parameters
        if ($filePath !== null) {
            $stmt->bindParam(':file_url', $filePath);
        }
        // Execute the statement
        $success = $stmt->execute();

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function getMedia($mediaId)
{
    global $pdo;

    // Prepare SQL statement to retrieve media details
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    return $media ? $media : false;
}

function getGallery()
{
    global $pdo;

    // Execute SQL query to select all gallery
    $stmt = $pdo->query("SELECT * FROM gallery");

    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the gallery array
        return $gallery;
    } else {
        // Return false if the query fails
        return array();
    }
}


// Function to delete a media with its associated file
function deleteMedia($mediaId)
{
    global $pdo;

    // Retrieve file URL of the media
    $stmt = $pdo->prepare("SELECT file_url FROM gallery WHERE id = ?");
    $stmt->execute([$mediaId]);
    $fileUrl = $stmt->fetchColumn();

    // Delete media record from the database
    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$mediaId]);

    // Delete media file from the server
    if (file_exists($fileUrl)) {
        unlink($fileUrl); // Delete file
    }

    return true; // media deleted successfully
}

/*############
## Excuses ###
#############*/
function addExcuse($studentId, $subject, $message, $file_url)
{
    global $pdo;

    try {
        // Insert data into the database
        $stmt = $pdo->prepare("INSERT INTO excuses (student_id, subject, message, file_url) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$studentId, $subject, $message, $file_url]);

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to update excuse details
function updateExcuse($excuseId, $title, $subject, $filePath)
{
    global $pdo;

    try {
        // Initialize SQL statement
        $sql = "UPDATE excuses SET subject = :subject";

        // If file details are provided, add them to the SQL statement
        if ($filePath !== null) {
            $sql .= ", file_url = :file_url";
        }

        // Complete the SQL statement
        $sql .= " WHERE id = :excuse_id";

        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':excuse_id', $excuseId);

        // If file details are provided, bind them to parameters
        if ($filePath !== null) {
            $stmt->bindParam(':file_url', $filePath);
        }
        // Execute the statement
        $success = $stmt->execute();

        return $success;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}


// Function to delete a excuse with its associated file
function deleteExcuse($excuseId)
{
    global $pdo;

    // Retrieve file URL of the excuse
    $stmt = $pdo->prepare("SELECT file_url FROM excuses WHERE id = ?");
    $stmt->execute([$excuseId]);
    $fileUrl = $stmt->fetchColumn();

    // Delete excuse record from the database
    $stmt = $pdo->prepare("DELETE FROM excuses WHERE id = ?");
    $stmt->execute([$excuseId]);

    // Delete excuse file from the server
    if (file_exists($fileUrl)) {
        unlink($fileUrl); // Delete file
    }

    return true; // excuse deleted successfully
}


function getExcuse($excuseId)
{
    global $pdo;

    // Prepare SQL statement to retrieve excuse details
    $stmt = $pdo->prepare("SELECT * FROM excuses WHERE id = ?");
    $stmt->execute([$excuseId]);
    $excuse = $stmt->fetch(PDO::FETCH_ASSOC);

    return $excuse ? $excuse : false;
}

function getExcuses()
{
    global $pdo;

    // Execute SQL query to select all excuses
    $stmt = $pdo->query("SELECT * FROM excuses ORDER BY id DESC");

    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $excuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the excuses array
        return $excuses;
    } else {
        // Return false if the query fails
        return array();
    }
}
function getStudentExcuses($studentId)
{
    global $pdo;

    // Execute SQL query to select all excuses
    $stmt = $pdo->query("SELECT * FROM excuses WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$studentId]);
    // Check if the query was successful
    if ($stmt) {
        // Fetch all rows as an associative array
        $excuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the excuses array
        return $excuses;
    } else {
        // Return false if the query fails
        return array();
    }
}
// Function to accept an excuse
function acceptExcuse($excuseId)
{
    global $pdo;

    try {
        // Update the status of the excuse to 1 (accepted)
        $stmt = $pdo->prepare("UPDATE excuses SET status = 1 WHERE id = ?");
        $stmt->execute([$excuseId]);

        return true; // Excuse accepted successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Function to reject an excuse
function rejectExcuse($excuseId)
{
    global $pdo;

    try {
        // Update the status of the excuse to 0 (rejected)
        $stmt = $pdo->prepare("UPDATE excuses SET status = 0 WHERE id = ?");
        $stmt->execute([$excuseId]);

        return true; // Excuse rejected successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// questions
// Function to store questions and answers in the database
function storeQuestion($exercise_id, $question, $answers, $correctAnswers, $multiple_answers)
{
    global $pdo;

    try {

        // Insert the question into the database
        $stmt = $pdo->prepare("INSERT INTO questions (question, exercise_id, multiple_answers) VALUES (?, ?, ?)");
        $stmt->execute([$question, $exercise_id, $multiple_answers]);
        $questionId = $pdo->lastInsertId();

        // Insert the answers into the database
        $stmt = $pdo->prepare("INSERT INTO question_answers (question_id, answer, is_correct) VALUES (?, ?, ?)");
        foreach ($answers as $key => $answer) {
            // Check if the current answer is marked as correct
            $isCorrect = in_array($key, $correctAnswers) ? 1 : 0;
            $stmt->execute([$questionId, $answer, $isCorrect]);
        }

        // Return true if data is stored successfully
        return true;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        // Return false if an error occurs
        return false;
    }
}

function updateQuestion($questionId, $exerciseId, $question, $multiple_answers, $answers, $correctAnswers)
{
    global $pdo;

    try {
        // Update the question in the questions table
        $stmt = $pdo->prepare("UPDATE questions SET question = ?, exercise_id = ?, multiple_answers = ? WHERE id = ?");
        $stmt->execute([$question, $exerciseId, $multiple_answers, $questionId]);

        // Delete existing answers for the question from the question_answers table
        $stmt = $pdo->prepare("DELETE FROM question_answers WHERE question_id = ?");
        $stmt->execute([$questionId]);

        // Insert the updated answers into the question_answers table
        $stmt = $pdo->prepare("INSERT INTO question_answers (question_id, answer, is_correct) VALUES (?, ?, ?)");
        foreach ($answers as $key => $answer) {
            // Check if the current answer is marked as correct
            $isCorrect = in_array($key, $correctAnswers) ? 1 : 0;
            $stmt->execute([$questionId, $answer, $isCorrect]);
        }

        return true; // Return true if the question and answers are updated successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false; // Return false if an error occurs
    }
}
// Function to store user privileges
function getQuestion($questionId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$questionId]);
        // Fetch all rows from the query result
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        $questionAnswers    = getQuestionAnswers($questionId) ?? array();
        $question['answers']    = $questionAnswers;
        return $question;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function deleteQuestion($questionId)
{
    global $pdo;

    try {
        // Delete the question from the questions table
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);

        // Delete the associated answers from the question_answers table
        $stmt = $pdo->prepare("DELETE FROM question_answers WHERE question_id = ?");
        $stmt->execute([$questionId]);

        return true; // Return true if the question and its answers are deleted successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false; // Return false if an error occurs
    }
}
// Function to store user privileges
function getQuestionAnswers($questionId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM question_answers WHERE question_id = ? ");
        $stmt->execute([$questionId]);
        // Fetch all rows from the query result
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $answers;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function getQuestions()
{
    global $pdo;

    try {
        // Query to fetch question questions with their answers
        $stmt = $pdo->prepare("SELECT * FROM questions ORDER BY id DESC");
        $stmt->execute();
        // Fetch all questions from the query result
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group answers by question
        foreach ($questions as &$question) {
            $questionAnswers = getQuestionAnswers($question['id']);
            $question['answers']    = $questionAnswers;
        }
        return $questions;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// exercises
// Function to store exercises and answers in the database
function storeExercise($title, $attempts)
{
    global $pdo;

    try {
        // Insert the exercise into the database
        $stmt = $pdo->prepare("INSERT INTO exercises (title, attempts) VALUES (?, ?)");
        $stmt->execute([$title, $attempts]);
        // Return true if data is stored successfully
        return true;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        // Return false if an error occurs
        return false;
    }
}

function updateExercise($exerciseId, $title, $attempts)
{
    global $pdo;

    try {
        // Update the exercise in the exercises table
        $stmt = $pdo->prepare("UPDATE exercises SET title = ?, attempts = ? WHERE id = ?");
        $stmt->execute([$title, $attempts, $exerciseId]);

        return true; // Return true if the exercise and answers are updated successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false; // Return false if an error occurs
    }
}
// Function to store user privileges
function getExercise($exerciseId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$exerciseId]);
        // Fetch all rows from the query result
        $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

        $exerciseQuestions    = getExerciseQuestions($exerciseId) ?? array();
        $exercise['questions']    = $exerciseQuestions;
        return $exercise;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function deleteExercise($exerciseId)
{
    global $pdo;

    try {
        // Delete the exercise from the exercises table
        $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = ?");
        $stmt->execute([$exerciseId]);

        return true; // Return true if the exercise and its answers are deleted successfully
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false; // Return false if an error occurs
    }
}
// Function to store user privileges
function getExerciseQuestions($exerciseId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE exercise_id = ? ");
        $stmt->execute([$exerciseId]);
        // Fetch all rows from the query result
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $questions;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function getExercises()
{
    global $pdo;

    try {
        // Query to fetch exercise exercises with their answers
        $stmt = $pdo->prepare("SELECT * FROM exercises ORDER BY id DESC");
        $stmt->execute();
        // Fetch all exercises from the query result
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group questions by exercise
        foreach ($exercises as &$exercise) {
            $exerciseQuestions = getExerciseQuestions($exercise['id']);
            $exercise['questions']    = $exerciseQuestions;
        }
        return $exercises;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function getExerciseCorrectAnswersIds($exerciseId)
{
    global $pdo;

    try {
        // Prepare SQL statement to retrieve IDs of correct answers for exercise questions
        $stmt = $pdo->prepare("SELECT qa.id
                            FROM questions q
                            INNER JOIN question_answers qa ON q.id = qa.question_id
                            WHERE q.exercise_id = ? AND qa.is_correct = 1");

        // Execute the statement with the exercise ID parameter
        $stmt->execute([$exerciseId]);

        // Fetch all IDs of correct answers from the query result
        $correctAnswerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Return the array of correct answer IDs
        return $correctAnswerIds;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

// Student Exercise Page
function calculateExerciseResult($submittedAnswersIds, $correctAnswersIds)
{
    // Initialize counter for correct answers
    $correctCount = 0;

    // Check each submitted answer
    foreach ($submittedAnswersIds as $submittedAnswer) {
        // Check if the submitted answer is correct
        if (in_array($submittedAnswer, $correctAnswersIds)) {
            // Increment the counter for correct answers
            $correctCount++;
        }
    }

    // Return the total count of correct answers
    return [
        'count' => $correctCount,
        'percentage' => round((($correctCount / count($correctAnswersIds)) * 100),  2)
    ];
}

function addExerciseResult($studentId, $exerciseId, $score, $percentage)
{
    global $pdo; // Assuming $pdo is your PDO object for database connection

    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO exercise_results (student_id, exercise_id, score, percentage) VALUES (?, ?, ?, ?)");

    // Bind parameters and execute the statement
    $stmt->execute([$studentId, $exerciseId, $score, $percentage]);

    // Check if the insertion was successful
    if ($stmt->rowCount() > 0) {
        return $pdo->lastInsertId(); // Success
    } else {
        return false; // Failed to insert
    }
}

function checkExerciseAttempts($student_id, $exercise_id)
{
    global $pdo;

    // Prepare and execute the query to get the allowed attempts for the exercise
    $stmt = $pdo->prepare("SELECT attempts FROM exercises WHERE id = ?");
    $stmt->execute([$exercise_id]);
    $allowed_attempts = $stmt->fetchColumn();

    // Prepare and execute the query to count the attempts made by the student for the exercise
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM exercise_results WHERE student_id = ? AND exercise_id = ?");
    $stmt->execute([$student_id, $exercise_id]);
    $exercise_attempts = $stmt->fetchColumn();

    // Check if the student has made fewer attempts than allowed
    if ($exercise_attempts < $allowed_attempts) {
        return true;
    } else {
        return false;
    }
}


function getExerciseResult($resultId)
{
    global $pdo;

    try {
        // Query to fetch exercise exercises with their answers
        $stmt = $pdo->prepare("SELECT * FROM exercise_results WHERE id = ?");
        $stmt->execute([$resultId]);
        // Fetch all exercises from the query result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}


function getExerciseResults($exerciseId)
{
    global $pdo; // Assuming $pdo is your PDO object for database connection

    // Prepare SQL statement to retrieve students who took the exercise and their results
    $stmt = $pdo->prepare("
        SELECT s.name AS student_name, s.academic_number, er.score, er.percentage, er.completed_at
        FROM students s
        INNER JOIN exercise_results er ON s.id = er.student_id
        WHERE er.exercise_id = ?
    ");

    // Bind the exercise ID parameter and execute the statement
    $stmt->execute([$exerciseId]);

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results
    return $results;
}

function getBestExerciseScores($exerciseId)
{
    global $pdo; // Assuming $pdo is your PDO object for database connection

    // Prepare SQL statement to retrieve the best score for each student for the specified exercise
    $stmt = $pdo->prepare("
        SELECT er.student_id, s.name AS student_name, s.academic_number, MAX(er.score) AS best_score, MAX(er.percentage) AS percentage, er.completed_at
        FROM exercise_results er
        INNER JOIN students s ON s.id = er.student_id
        WHERE er.exercise_id = ?
        GROUP BY er.student_id
    ");

    // Bind the exercise ID parameter and execute the statement
    $stmt->execute([$exerciseId]);

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results
    return $results;
}



// Settings

// Function to get settings values
function getSettings()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    return $settings;
}

// Function to update settings
function updateSettings($sitetSite, $aboutSite, $footerText)
{
    global $pdo;

    $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, about_site = ?, footer_text = ?");
    $stmt->execute([$sitetSite, $aboutSite, $footerText]);

    return true; // Returns the number of affected rows
}
