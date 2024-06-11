<?php
session_start();
$table = $_SESSION['table'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title> Gestore Aziende Ospedaliere
    </title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>

    <header>

        <!-- Navigation -->
        <div class="container-xl mx-auto pt-4">
            <div class="bg-dark rounded-3 position-relative">

                <nav class="navbar navbar-expand-lg bg-body-primary justify-content-between">

                    <div class="pb-1 fw-semibold text-light d-flex gap-2 align-items-center">
                        <img width="100" class="ms-4" src="../logo.png">
                        <h3 class="m-0 p-0 fw-bold">Gestore Aziende Ospedaliere</h3>
                    </div>
                    <a class="btn btn-outline-light me-5" href="../index.php">
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
            <div class='d-flex justify-content-between mb-5 align-items-center'>
                <h3 class=' fw-bold text-capitalize'> <?php echo $table ?> </h3>
                <a class='btn btn-outline-secondary me-4 fs-5' href="view.php"> Indietro <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='currentColor' class='bi bi-arrow-left-short' viewBox='0 0 16 16'>
                        <path fill-rule='evenodd' d='M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5' />
                    </svg></a>
            </div>
            <form method="POST" action="opmanager.php" class="styled-form w-80">
            <?php include 'utility.php';
            showForm(); ?>

            <button class='btn btn-secondary fs-5' type='submit' value='insert' name='operation'>Salva</button>
            <button class="btn btn-outline-danger fs-6" onclick='clearForm()'>Cancella</button>
            </form>
        </div>



    </section>
    <script>
                    function clearForm() {
                        var inputs = document.querySelectorAll('input[type=text]');
                        inputs.forEach(function(input) {
                            input.value = '';
                        });
                    }
        </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>

</html>

<?php

function showForm()
{
    global $table;

    $conn = connectToDatabase();
    $form = [];

    $columns = getColumnsInfo($conn, $table);
   

    if (isset($_SESSION['inserted_data'])) {
        $alreadyInsertedValues = $_SESSION['inserted_data'];
        $alreadyInsertedValues = array_change_key_case($alreadyInsertedValues, CASE_LOWER);
        unset($_SESSION['inserted_data']);
    } else {
        $alreadyInsertedValues = NULL;
    }

    $form += getInputFields($conn, $columns, NULL, $alreadyInsertedValues);

    echo implode('', $form);
}



?>