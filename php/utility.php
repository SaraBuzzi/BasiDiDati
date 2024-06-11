<?php
// error_reporting (E_ERROR); //hides warnings

$connectionString = "host=localhost port=5432 dbname=ospedali user=postgres password=dbadmin";

//Connection to Database
function connectToDatabase()
{
    global $connectionString;
    $connection = pg_connect($connectionString);
    if (!$connection) {
        echo '<br> Connessione al database fallita. <br>';
        exit();
    }

    return $connection;
}



function buildTable($columns, $result)
{
    $string = "<table class='styled-table w-100'>";
    $string .= "<tr>";
    foreach ($columns as $column) {
        $string .= "<th>" . htmlspecialchars($column) . "</th>";
    }
    $string .= "<th></th>";
    $string .= "</tr>";

    foreach ($result as $row) {
        $string .= "<tr><form action='opmanager.php' method='POST'>";
        foreach ($columns as $column) {
            $string .= "<td> <input type='hidden' name='{$column}' value='{$row[$column]}'>" . htmlspecialchars($row[$column]) . "</td>";
        }
        $string .= "<td><button class='btn btn-outline-info' type='submit' value='edit' name='operation'>
        <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-square' viewBox='0 0 16 16'>
            <path d='M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z'/>
            <path fill-rule='evenodd' d='M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z'/>
        </svg>
       
                    </button>

                    <button class='btn btn-outline-danger' type='submit' value='delete' name='operation'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash' viewBox='0 0 16 16'>
                    <path d='M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z'/>
                    <path d='M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z'/>
                  </svg>
                    </button></td>";
        $string .= "</form></tr>";
    }

    $string .= "</table>";

    return $string;
}




function getInputFields($connection, $columns, $disabledFields = NULL, $valuesForFields = NULL)
{
    $formFields = array();
    foreach ($columns as $col) {
        $toedit = ($disabledFields) ? !in_array($col['column_name'], $disabledFields) : true;
        $value = ($valuesForFields && array_key_exists($col['column_name'], $valuesForFields)) ? $valuesForFields[$col['column_name']] : NULL;
        $formFields[$col['ordinal_position']] = buildInputField($connection, $col, $toedit, $value);
    }
    return $formFields;
}


function buildInputField($connection, $column_data, $editable = true, $value = NULL)
{
    $name = ucfirst($column_data['column_name']);
    $field_type = $column_data['udt_name'];

    if (stristr($name, "hashed")) {
        $name = str_ireplace("hashed", "Plaintext", $name);
        return inputPassword($name, $column_data['is_nullable'] == 'NO', $editable, $value);
    }
    switch ($field_type) {
        case 'bpchar':
            return inputText($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'varchar':
            return inputText($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'numeric':
            return inputNumber($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'date':
            return inputDate($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'time':
            return inputTime($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'timestamp':
            return inputDateTime($name, $column_data['is_nullable'] == 'NO', $editable, $value);
        case 'bool':
                return inputBoolean($name);
        default:
            //fascia urgenza e giorni della settimana
            $query = "SELECT enum_range(NULL::{$field_type})";
            try {
                $result = pg_query($connection, $query);
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header("Refresh:0");
            }
            $enumValues = substr(pg_fetch_array($result)['enum_range'], 1, -1); //Trim { and }
            return inputSelect($name, $column_data['is_nullable'] == 'NO', $value, explode(',', $enumValues));
    }
}




function isRequired($required)
{
    return ($required) ? "required" : "";
}

function isEditable($editable)
{
    return ($editable) ? "" : "disabled";
}

function getValue($value)
{
    return ($value) ? "value='{$value}'" : "";
}

function inputText($name, $required, $editable, $value)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $getValue = getValue($value);

    return <<<IT
    <label class="form-label" for="{$name}">{$name}:</label>
    <input class="form-control rounded-pill" type="text" name="{$name}" id="{$name}" {$getValue} {$getRequired} {$getEditable}>
    <br>
    IT;
}

function inputNumber($name, $required, $editable, $value)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $getValue = getValue($value);

    return <<<IT
    <label class="form-label" for="{$name}">{$name}:</label>
    <input class="form-control rounded-pill" type="number" name="{$name}" id="{$name}" {$getValue} {$getRequired} {$getEditable}>
    <br>
    IT;
}

function inputBoolean($name)
{
    return <<<IT
        <label class="form-check-label form-label" for="{$name}">{$name}:</label>
        <input name="true" type="checkbox" value="true" class='ms-1'>
        <label class="form-check-label" for="true">True</label>
        
        <input name="false" type="checkbox" value="false" class='ms-3'>
        <label class="form-check-label" for="false">False</label>
        <br>
        <br>
    IT;
}

function inputDate($name, $required, $editable, $value)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $getValue = getValue($value);

    return <<<IT
        <label class="form-label" for="{$name}">{$name}:</label>
        <input class="form-control rounded-pill" name="{$name}" type="date" {$getValue} {$getRequired} {$getEditable}>
        <br>
    IT;
}

function inputTime($name, $required, $editable, $value)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $getValue = getValue($value);

    return <<<IT
        <label class="form-label" for="{$name}">{$name}:</label>
        <input class="form-control rounded-pill" name="{$name}" type="time" {$getValue} {$getRequired} {$getEditable}>
        <br>
    IT;
}

function inputDateTime($name, $required, $editable, $value)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $getValue = getValue($value);

    return <<<IT
        <label class="form-label" for="{$name}">{$name}:</label>
        <input class="form-control rounded-pill" name="{$name}" type="datetime-local" {$getValue} {$getRequired} {$getEditable}>
        <br>
    IT;
}

function inputSelect($name, $required, $value, $options)
{
    $getRequired = isRequired($required);
    $getValue = getValue($value);
    $optionsString = "";

    foreach ($options as $option) {
       
        $selected = ($option == $value) ? "selected" : "";

        
        $optionsString .= "<option value='{$option}' {$selected}>{$option}</option>";
    }

    
    if ($getValue === "") {
        $optionsString = '<option value="" selected>--</option>' . $optionsString;
    } else {
        $optionsString = '<option value="">--</option>' . $optionsString;
    }

    
    return <<<HTML
    <label class="form-label" for="{$name}">{$name}:</label>
    <select class="form-selected rounded-pill p-2 w-100" name="{$name}" {$getRequired}>
    {$optionsString}
    </select>
    <br><br>

HTML;
}

function inputPassword($name, $required, $editable = true, $value = NULL)
{
    $getRequired = isRequired($required);
    $getEditable = isEditable($editable);
    $valueStr = getValue($value);

    return <<<EOD
        <label class="form-label" for="{$name}">{$name}:</label>
        <input class="form-control rounded-pill" type="password" name="{$name}" id="{$name}" {$valueStr} {$getRequired} {$getEditable}>
        <br>
    EOD;
}


function getColumnsInfo($connection, $tableName)
{

    $query = "SELECT * FROM information_schema.columns WHERE table_name = $1 ORDER BY ordinal_position";

    try {

        $result = pg_query_params($connection, $query, array($tableName));

        if (!$result) {
            throw new Exception(pg_last_error($connection));
        }
        return pg_fetch_all($result);
    } catch (Exception $e) {
        error_log("Errore nella lettura dal Database: " . pg_last_error($connection));
        header("Refresh:0");
    }
}

function getPrimaryKeys($connection, $tableName)
{
    $table = strtolower($tableName);
    $query = <<<QRY
    SELECT column_name
    FROM information_schema.table_constraints TC JOIN information_schema.key_column_usage KCU
    ON TC.constraint_name = KCU.constraint_name
    WHERE TC.table_name = $1 AND constraint_type = 'PRIMARY KEY';
    QRY;
    try {
        $results = pg_query_params($connection, $query, array($table));
        $pkeys = array_map(fn ($el) => $el['column_name'], pg_fetch_all($results));
    } catch (Exception $e) {
        error_log("Errore nella lettura dal Database: " . pg_last_error($connection));
        header("Refresh:0");
    }
    return $pkeys;
}

function getForeignKeyConstraints($connection, $tableName)
{
    $query = <<<QRY
    SELECT A.constraint_name, A.column_name, B.table_name AS referredTable, B.column_name AS referredColumn, B.ordinal_position
    FROM (information_schema.referential_constraints NATURAL JOIN information_schema.key_column_usage) AS A
    JOIN information_schema.key_column_usage B 
    ON A.unique_constraint_name = B.constraint_name
    AND A.position_in_unique_constraint = B.ordinal_position
    WHERE A.constraint_name IN (
      SELECT constraint_name
      FROM information_schema.key_column_usage
      WHERE table_name = $1
      AND constraint_name LIKE '%_fkey'
    )
    ORDER BY B.table_name, B.ordinal_position;
    QRY;


    $result = pg_query_params($connection, $query, array($tableName));


    if (!$result) {
        error_log("Errore nella lettura dal Database: " . pg_last_error($connection));
        return [];
    }

    $foreignKeyConstraints = pg_fetch_all($result);

    return $foreignKeyConstraints ? $foreignKeyConstraints : [];
}


function getTables($conn) {
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }

    return $tables;
}

function getUsersTables($conn) {
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }

    return $tables;
}
