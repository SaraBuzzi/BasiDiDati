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
        $_SESSION['table'] = strtolower($table);
        $attributes = array_filter($attributes, fn ($el) => $el);
        $names = implode(", ", array_keys($attributes));
        $values = implode(", ", array_map(fn ($el) => "'$el'", array_values($attributes)));
        insertIntoDatabase($conn, $table, $names, $values);
        header("Location: /basididati/Prog/php/view.php?table={$table}");
        break;
    case 'update':
        $_SESSION['table'] = strtolower($table);
        updateIntoDatabase($conn, $table, $attributes);
        unset($_SESSION['edit_data']);
        header("Location: /basididati/Prog/php/view.php?table={$table}");
        break;
    case 'delete':
        deleteFromDatabase($conn, $table);
        break;
    case 'login-patient':
        $user = $_POST['username'];
        $password = $_POST['password'];
        loginPatient($conn, $user, $password);
        break;
    case 'login-worker':
        $user = $_POST['username'];
        $password = $_POST['password'];
        loginWorker($conn, $user, $password);
        break;
    case 'logout':
        break;
    case 'select-table':
        $_SESSION['table'] = strtolower($_POST['select']);
        header("Location: /basididati/Prog/php/view.php");
        break;

}



function deleteFromDatabase($conn, $table)
{
    $condition = "WHERE ";

    foreach ($_POST as $key => $value) {
        $condition .= $key . " = '" . $value . "' AND ";
    }
    $condition = substr($condition, 0, -4);
    $query = "DELETE FROM " . $table . " " . $condition;
    
    try {
        $result = pg_query($conn, $query);
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        } 
        header("Location: /basididati/Prog/php/view.php");
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: /basididati/Prog/php/view.php");
        exit();
    }
    
}



function insertIntoDatabase($conn, $table, $attributes, $values)
{

    $query = "INSERT INTO {$table} ({$attributes}) VALUES ({$values});";
    
    try {
        $results = pg_query($conn, $query);
        
        if (!$results || pg_result_error($results)) {
            throw new Exception(pg_last_error($conn));
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

function loginPatient($conn, $user, $password) {

}

function loginWorker($conn, $user, $password) {

}





function parsePostValues() {
    unset($_POST['operation']);
    unset($_POST['table']);

    $attributes = array();

   
    foreach ($_POST as $k => $v) {
       
        $ks = explode(",_", $k);
        
        
        if (count($ks) > 1) {
            $vs = explode(",", $v);
        } else {
            $vs = array($v);
        }

        
        foreach ($ks as $index => $key) {
            $value = $vs[$index];

            if (stripos($key, "plaintext") !== false) {
                $key = str_ireplace("plaintext", "Hashed", $key);
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            $attributes[$key] = $value;
        }
    }
    return $attributes;
}



