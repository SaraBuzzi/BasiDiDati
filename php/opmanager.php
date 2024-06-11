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
$operation = $_POST['operation'];
unset($_POST['operation']);


switch ($operation) {
    case 'edit':
        header("Location: /basididati/Prog/php/update.php?table={$table}");
        break;
    case 'insert':
        $_SESSION['table'] = $table;
        $attributes = array_filter($attributes, fn ($el) => $el);
        $names = implode(", ", array_keys($attributes));
        $values = implode(", ", array_map(fn ($el) => "'" . $el . "'", array_values($attributes)));
        insertIntoDatabase($connection, $table, $names, $values);
        header("Location:  /basididati/Prog/php/view.php?table={$table}");

        break;
    case 'update':
        $_SESSION['table'] = $table;
        updateIntoDatabase($connection, $table, $attributes);
        unset($_SESSION['edit_data']);
        header("Location: /basididati/Prog/php/view.php?table={$table}");
        break;
    case 'delete':
        deleteFromDatabase($conn, $table);
        break;
    // case 'login_as_patient':
    //     loginAsPatient($connection);
    //     header("Location: /basididati/Prog/index.php");
    //     break;
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
    } catch (Exception $e) {
        $_SESSION['inserted_data'] = $_POST;
        echo '<br> Operazione non riuscita <br>';
        header("Location:/basididati/Prog/php/insert.php");
        exit();
    }
}

function updateIntoDatabase($connection, $table, $values)
{
    print_r($values);
    $pkeys = getPrimaryKeys($connection, $table);
    $findCondition = "WHERE ";
    $editCondition = "";
    foreach ($values as $k => $v) {
        if (!$v) continue; //Avoid statements like "Attribute = NULL"
        if (in_array(strtolower($k), $pkeys)) {
            $findCondition .= "{$k} = '{$v}' AND ";
        }
        $editCondition .= "{$k} = '{$v}', ";
    }
    $findCondition = substr($findCondition, 0, -4);
    $editCondition = substr($editCondition, 0, -2);
    $query = "UPDATE {$table} SET {$editCondition} {$findCondition}";
    echo $query;
    try {
        $results = pg_query($connection, $query);
    } catch (Exception $e) {
        echo '<br> Operazione non riuscita <br>';
        header("Location:/basididati/Prog/php/update.php?table={$table}");
        return;
    }
}
