<?php
session_start();
include 'utility.php';
$conn = connectToDatabase();





if (isset($_POST['table'])) {
    $table = $_POST['table'];
    $_SESSION['table'] = $table;
} else {
    $table = $_SESSION['table'];
}

$operation = strtolower($_POST['operation']);
$attributes = parsePostValues();



switch ($operation) {
    case 'edit':
        $_SESSION['edit_data'] = $_POST;
        header("Location: /basididati/Prog/php/update.php?table={$table}");
        break;
    case 'insert':
        $_SESSION['table'] = $table;
        $attributes = array_filter($attributes, fn ($el) => $el);
        $names = implode(", ", array_keys($attributes));
        $values = implode(", ", array_map(fn ($el) => "'$el'", array_values($attributes)));
        insertIntoDatabase($conn, $table, $names, $values);
        header("Location: /basididati/Prog/php/view.php?table={$table}");
        break;
    case 'update':
        $_SESSION['table'] = $table;
        updateIntoDatabase($conn, $table, $attributes);
        unset($_SESSION['edit_data']);
        header("Location: /basididati/Prog/php/view.php?table={$table}");
        break;
    case 'delete':
        deleteFromDatabase($conn, $table);
        break;
        case 'login_as_patient':
            loginAsPatient($connection);
            header("Location: /basididati/Prog/index.php");
            break;
        // case 'login_as_worker':
        //     loginAsWorker($connection);
        //     header("Location: /basididati/Prog/index.php");
        //     break;
        // case 'logout':
        //     unset($_SESSION['logged_user']);
        //     header("Location:  /basididati/Prog/login.php");
        //     break;
        // case 'view_table_by_select':
        //     $_SESSION['table'] = strtolower($_POST['Tabella']);
        //     header("Location: /basididati/Prog/php/view.php?table={$table}");
        //     break;
}



function deleteFromDatabase($conn, $table)
{
    $condition = "WHERE ";

    foreach ($_POST as $key => $value) {
        $condition .= $key . " = '" . $value . "' AND ";
    }
    $condition = substr($condition, 0, -4);
    $query = "DELETE FROM " . $table . " " . $condition;
    $result = pg_query($conn, $query);
    if (!$result) {
        echo '<br> Operazione non riuscita <br>';
        exit();
    } else {
        header("Location: /basididati/Prog/php/view.php?table={$table}");
    }
}



function insertIntoDatabase($conn, $table, $attributes, $values)
{

    $query = "INSERT INTO {$table} ({$attributes}) VALUES ({$values});";
    print_r($query);

    try {

        $results = pg_query($conn, $query);


        if (!$results) {
            $_SESSION['error_message'] = "Dati inconsistenti con il resto del database"; 
            // INSERIMENTO FK SBAGLIATO
        }
    } catch (Exception $e) {

        $_SESSION['inserted_data'] = $_POST;

        $_SESSION['error_message'] = $e->getMessage();

        header("Location:/basididati/Prog/php/insert.php");
        exit();
    }
}




function updateIntoDatabase($conn, $table, $values)
{
    $primaryKeys = getPrimaryKeys($conn, $table);
    $editCondition = "";
    $setValues = "";
    foreach ($values as $key => $value) {
        echo $key . '<br>';
    }

    foreach ($values as $key => $value) {
        if (!$value) continue;
        if (in_array(strtolower($key), $primaryKeys)) {
            $editCondition .= "{$key} = '{$value}' AND ";
        } else {
            $setValues .= "{$key} = '{$value}', ";
        }
    }

    $editCondition = rtrim($editCondition, " AND ");
    $setValues = rtrim($setValues, ", ");

    $query = "UPDATE {$table} SET {$setValues} WHERE {$editCondition}";
    echo $query;

    try {
        $results = pg_query($conn, $query);
        if (!$results) {
            throw new Exception(pg_last_error($conn));
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e -> getMessage();
        header("Location:/basididati/Prog/php/update.php");
        exit();
    }
}






function parsePostValues()
{
    unset($_POST['operation']);
    unset($_POST['table']);

    $attributes = array();

    foreach ($_POST as $k => $v) {
        $ks = explode(",_", $k);
        if (count($ks) > 1) {
            $vs = array_values(explode(",", $v));
        } else {
            $vs = array_values(array($v));
        }
        $i = 0;
        foreach ($ks as $key) {
            $value = $vs[$i++];
            if (stristr($key, "plaintext")) {
                $key = str_ireplace("plaintext", "Hashed", $key);
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            $attributes[$key] = $value;
        }
    }


    return $attributes;
}


function loginAsPatient($connection)
{

    global $DEFAULT_DIR;

    $query = "SELECT * FROM UtenzaPaziente WHERE paziente = $1;";
    try {
        $result = pg_fetch_array(pg_query_params($connection, $query, array($_POST['Username'])));
        if (password_verify($_POST['Password'], $result['hashedpassword'])) {
            $_SESSION['logged_user'] = array('username' => $_POST['Username'], 'type' => 'patient');
            header("Location:/basididati/Prog/php/view.php");
        } else {
            $_SESSION['error_message'] = "Credenziali non valide";
            header("Location:/basididati/Prog/login.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e -> getMessage();
        header("Location:/basididati/Prog/login.php");
        exit();
    }
}