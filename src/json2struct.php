<?php
// Controlla se è stato inviato qualcosa
if (!isset($_POST['json'])) {
    echo "Nessun dato ricevuto.", PHP_EOL;
}
$json = $_POST['json'];

$int_type = isset($_POST['int_type']) ? $_POST['int_type'] : "int";
$float_type = isset($_POST['float_type']) ? $_POST['float_type'] : "float";

$max_items_array = isset($_POST['max_items_array']) ? intval($_POST['max_items_array']) : 100;
if ($max_items_array < 1) $max_items_array = 1;
if ($max_items_array > 255) $max_items_array = 255;

$max_char_length = isset($_POST['max_char_length']) ? intval($_POST['max_char_length']) : 100;
if ($max_char_length < 1) $max_char_length = 1;
if ($max_char_length > 256) $max_char_length = 256;

$data = json_decode($json);
if ($data === null) {
    echo "Errore: JSON non valido.", PHP_EOL;
    exit(1);
}

$structs = [];
$counters = [];
$structs_ptrs = [];
$structs_key_names = [];

function sanitize_name($name) {
	if (is_numeric($name)) {
		return "the_" . $name;
    }
    return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
}

function generate_struct($name, $data) {
	global $int_type;
	global $float_type;
	global $max_items_array;
	global $max_char_length;
    global $structs;
    global $structs_key_names;
    global $structs_ptrs;
    global $counters;
    $struct_name = ucfirst(sanitize_name($name)) . "_s";
    
    $struct_ptr_name = ucfirst(sanitize_name($name)) . "_ptr_s";
    
    $struct_key_name = ucfirst(sanitize_name($name)) . "_json_key_s";
    
    $fields = [];
    
    $fields_ptrs = [];
    $fields_key_names = [];

    foreach ($data as $key => $value) {
        $key_s = sanitize_name($key);
        $flag_name = $key_s . "_defined";
        $counter_name = $key_s . "_counter";
        $ptr_name = $key_s;
        
        $fields_ptrs[] = "    const char *$ptr_name;";
		$fields_key_names[] = "    \"" . $key . "\",";

	if (is_object($value)) {
		$nested_name = ucfirst($key_s);
		if (array_key_exists($nested_name . "_s", $structs)) {
			$counter = -1;
			if (array_key_exists($nested_name . "_s", $counters)) {
				$counter = $counters[$nested_name . "_s"];
			} 
			$counter++;
			$counters[$nested_name . "_s"] = $counter;
			$nested_name .= $counter;
		}		
		generate_struct($nested_name, $value);
		$fields[] = "    struct $nested_name" . "_s $key_s;";
		$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
	} else if (is_array($value) && array_values($value) !== $value) {
		$nested_name = ucfirst($key_s);
		if (array_key_exists($nested_name . "_s", $structs)) {
			$counter = -1;
			if (array_key_exists($nested_name . "_s", $counters)) {
				$counter = $counters[$nested_name . "_s"];
			} 
			$counter++;
			$counters[$nested_name . "_s"] = $counter;
			$nested_name .= $counter;
		}
		generate_struct($nested_name, $value);
		$fields[] = "    struct $nested_name" . "_s " . $key_s . "[" . $max_items_array . "];";
		$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
		$fields[] = "    unsigned int $counter_name;";
	} else if (is_array($value)) {
			if (is_object($value[0])) {
				$nested_name = ucfirst($key_s);
				if (array_key_exists($nested_name . "_s", $structs)) {
					$counter = -1;
					if (array_key_exists($nested_name . "_s", $counters)) {
						$counter = $counters[$nested_name . "_s"];
					} 
					$counter++;
					$counters[$nested_name . "_s"] = $counter;
					$nested_name .= $counter;
				}
				generate_struct($nested_name, $value[0]);
				$fields[] = "    struct $nested_name" . "_s " . $key_s . "[" . $max_items_array . "];"; 
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
			} else if (is_int($value[0])) {
				$fields[] = "    /* array di valori semplici: $key_s */";
				$fields[] = "    $int_type $key_s" . "[" . $max_items_array . "];";
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
				$fields[] = "    unsigned int $counter_name;";
			} else if (is_bool($value[0])) {
				$fields[] = "    /* array di valori semplici: $key_s */";
				$fields[] = "    unsigned int $key_s" . "[" . $max_items_array . "];";
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
				$fields[] = "    unsigned int $counter_name;";
			} else if (is_array($value[0])) {
				$nested_name = ucfirst($key_s);
				if (array_key_exists($nested_name . "_s", $structs)) {
					$counter = -1;
					if (array_key_exists($nested_name . "_s", $counters)) {
						$counter = $counters[$nested_name . "_s"];
					} 
					$counter++;
					$counters[$nested_name . "_s"] = $counter;
					$nested_name .= $counter;
				}
				generate_struct($nested_name, $value[0]);
				$fields[] = "    struct $nested_name" . "_s " . $key_s . "[" . $max_items_array . "];";
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
				$fields[] = "    unsigned int $counter_name;";
			} elseif (is_float($value[0])) {
				$fields[] = "    /* array di valori semplici: $key_s */";
				$fields[] = "    $float_type $key_s" . "[" . $max_items_array . "];";
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
			} elseif (is_string($value[0])) {
				$fields[] = "    /* array di valori semplici: $key_s */";
				$fields[] = "    char $key_s" . "[" . $max_items_array . "][" . $max_char_length . "];";
				$fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
				$fields[] = "    unsigned int $counter_name;";
			} else {
				$fields[] = "    /* tipo non riconosciuto per $key_s */";
			}
        } else if (is_int($value)) {
            $fields[] = "    $int_type $key_s;";
            $fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
        } else if (is_float($value)) {
            $fields[] = "    $float_type $key_s;";
            $fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
        } else if (is_string($value)) {
            $fields[] = "    char $key_s" . "[" . $max_char_length . "];";
            $fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
        } else if (is_bool($value)) {
            $fields[] = "    unsigned int $key_s; /* bool 0=false, 1=true*/";
            $fields[] = "    unsigned int $flag_name; /* bool 0=null, 1=defined */";
        } else {
            $fields[] = "    /* tipo non riconosciuto per $key_s */";
        }
    }
			
    $struct_code = "struct $struct_name {\n" . implode("\n", $fields) . "\n};\n";
    $structs[$struct_name] = $struct_code;
    
    $struct_code = "typedef struct {\n" . implode("\n", $fields_ptrs) . "\n} $struct_ptr_name;\n";
    $structs_ptrs[$struct_ptr_name] = $struct_code;
    
    $struct_code = "const $struct_ptr_name $struct_key_name = {\n" . implode("\n", $fields_key_names) . "\n};\n";
    $structs_key_names[$struct_key_name] = $struct_code;
}

generate_struct("root", $data);

echo "#ifndef JSON_KEYS_H", PHP_EOL;
echo "#define JSON_KEYS_H", PHP_EOL;

echo PHP_EOL;

echo "/* Struttura dei dati */", PHP_EOL;
// stampa tutte le struct, annidate prima
foreach ($structs as $s) {
    echo $s, PHP_EOL;
}

echo "/* Struttura dei puntatori alle chiavi JSON */", PHP_EOL;
foreach ($structs_ptrs as $s) {
    echo $s, PHP_EOL;
}

echo "/* Struttura delle chiavi JSON */", PHP_EOL;
foreach ($structs_key_names as $s) {
    echo $s, PHP_EOL;
}

echo PHP_EOL;

echo "#endif", PHP_EOL;

?>

