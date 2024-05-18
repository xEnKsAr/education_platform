<?php
include('./functions.php');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>منصة تعليمية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" integrity="sha512-t4GWSVZO1eC8BM339Xd7Uphw5s17a86tIZIj8qRxhnKub6WoyhnrxeCIMeAqBPgdZGlCcG2PrZjMc+Wr78+5Xg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/../assets/css/app.css" />
</head>

<body class="bg-white">
    <div class="container-fluid">
        <div class="row min-vh-100 align-items-center">
            <div class="col-lg-6 mx-auto ">
                <div class="bg-light shadow text-muted py-5">
                    <h1 class="text-center mb-3">إنشاء حساب جديد</h1>
                    <div class="px-3">
                        <?= getFlashMessage('success'); ?>
                        <?= getFlashMessage('error'); ?>
                    </div>
                    <form method="POST" action="<?= base_url() . '/backend/student/signup.php' ?>" class="rounded px-3 px-md-5 w-100">
                        <div class=" my-3">
                            <input type="text" name="name" class="form-control rounded-0" placeholder="الاسم" required />
                        </div>
                        <div class=" my-3">
                            <input type="text" name="academic_number" class="form-control rounded-0" placeholder="الرقم الاكاديمي" required />
                        </div>
                        <div class=" my-3">
                            <input type="text" name="phone" class="form-control rounded-0" placeholder="الجوال" required />
                        </div>
                        <div class=" mb-3">
                            <input type="password" name="password" class="form-control rounded-0" placeholder="كلمة المرور" required />
                        </div>
                        <div class="my-3">
                            <input type="submit" class="btn w-100 btn-success rounded-0" value="إنشاء حساب" />
                        </div>
                        <div class="text-center">
                            <a href="./index.php" class="text-primary">
                                تسجيل الدخول
                                <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js" integrity="sha512-VK2zcvntEufaimc+efOYi622VN5ZacdnufnmX7zIhCPmjhKnOi9ZDMtg1/ug5l183f19gG1/cBstPO4D8N/Img==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="/../assets/js/app.js"></script>
</body>

</html>