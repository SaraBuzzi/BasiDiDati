<?php

session_start();

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title> Gestore Aziende Ospedaliere
    </title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>



    <header>

        <!-- Navigation -->
        <div class="container-xl mx-auto pt-4">
            <div class="bg-dark rounded-3 position-relative">

                <nav class="navbar navbar-expand-lg bg-body-primary justify-content-between">

                    <div class="pb-1 fw-semibold text-light d-flex gap-2 align-items-center">
                        <img width="100" class="ms-4" src="logo.png">
                        <h3 class="m-0 p-0 fw-bold">Gestore Aziende Ospedaliere</h3>
                    </div>
                    <a class="btn btn-outline-light me-5" href="index.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z" />
                        </svg>
                    </a>

                </nav>


            </div>
        </div>
    </header>

    <section class="container-xl mx-auto my-5">
        <div class="bg-white mx-auto p-5 rounded-4 fit-content border border-secondary border-4 ">
            <?php

            if (!isset($_SESSION['logged_user'])) {
                header("Location: /basididati/ProgettoBD/login.php");
                exit();
            }

            $loggedUser = $_SESSION['logged_user'];

            switch ($loggedUser['type']) {
                case 'patient':
                    echo userPatient();
                    break;
                case 'worker':
                    echo userWorker();
                    break;
            }

            ?>
        </div>



    </section>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>

</html>

<?php


function userPatient() {}



function userWorker() {}    






?>