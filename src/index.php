<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>JSON → Struct for ANSI-C language</title>
  <script src="./js/jquery-3.6.0.min.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .container { display: flex; align-items: flex-start; gap: 20px; }
    textarea { flex: 0 0 40%; height: 600px; resize: none; font-family: monospace; }
    .buttons { display: flex; flex-direction: column; gap: 10px; }
    button { padding: 8px 12px; font-size: 14px; cursor: pointer; }

	.inputs {
	  display: flex;
	  flex-direction: column; /* forza ogni elemento a stare su una riga */
	  gap: 10px;              /* distanza verticale tra le scelte */
	}

	.inputs label {
	  flex-direction: column;
	  margin-left: 0px;
	  margin-right: 5px;      /* spazio tra label e input */
	}

	.inputs input,
	.inputs select {
	  vertical-align: middle;
	}
	
	.input-group {
	  display: flex;
	  align-items: center;
	  gap: 5px; /* distanza label/input */
	}

    #validationResult { margin-top: 10px; font-weight: bold; }
    #saveStatus { margin-top: 6px; font-size: 13px; }
  </style>
</head>
<body>
  <h2 align=center>Converter JSON → Struct ANSI-C language</h2>

<div class="inputs">
	<div class="input-group">
	<label for="max_items_array">Max Items Array (1-255): </label>
	<input type="number" id="max_items_array" min="1" max="255" value="100">
	</div>
	<div class="input-group">
	<label for="max_char_length">Max Char Length (1-256):</label>
	<input type="number" id="max_char_length" min="1" max="256" value="100">(add 1 byte for the NULL terminator of the string)
	</div>
	<div class="input-group">
	<label for="int_type">Int Type:</label>
	<select id="int_type" name="int_type">
	  <option value="int" selected>int</option>
	  <option value="unsigned int">unsigned int</option>
	  <option value="long">long</option>
	  <option value="unsigned long">unsigned long</option>
	  <option value="int8_t">int8_t</option>
	  <option value="uint8_t">uint8_t</option>
	  <option value="int16_t">int16_t</option>
	  <option value="uint16_t">uint16_t</option>
	  <option value="int32_t">int32_t</option>
	  <option value="uint32_t">uint32_t</option>
	  <option value="int64_t">int64_t</option>
	  <option value="uint64_t">uint64_t</option>
	</select>
	</div>
	<div class="input-group">
	<label for="float_type">Float Type:</label>
	<select id="float_type" name="float_type">
		<option value="float" selected>float</option>
		<option value="double">double</option>
		<option value="long double">long double</option>
	  </select>
	</div>
</div>
<br></br>
<div class="container">
    <textarea id="json" placeholder="Inserisci JSON qui..."></textarea>
    <div class="buttons">
      <button id="convert">→ Converti →</button>
      <button id="pretty">Pretty Print JSON</button>
      <button id="validate">Valida JSON</button>
      <button id="save-json">Salva JSON</button>
      <button id="clear-left">Clear JSON</button>
      <button id="clear-right">Clear Struct</button>
    </div>
    <textarea id="struct" placeholder="Output Struct..."></textarea>
  </div>

  <div id="validationResult"></div>
  <div id="saveStatus"></div>

  <script>
    $(function(){
      const KEY = "savedJson";

      // Ripristina JSON salvato all'apertura
      const saved = localStorage.getItem(KEY);
      if (saved) {
        $("#json").val(saved);
        $("#saveStatus").text("Ripristinato da localStorage.").css("color", "green");
      }

// Convert button
$("#convert").click(function(){
  const jsonData = $("#json").val();
  const maxItems = $("#max_items_array").val();
  const maxCharLength = $("#max_char_length").val();
  const intType = $("#int_type").val();
  const floatType = $("#float_type").val(); // nuovo campo

  $.post("json2struct.php", { 
    json: jsonData, 
    max_items_array: maxItems,
    max_char_length: maxCharLength,
    int_type: intType,
    float_type: floatType
  }, function(response){
    $("#struct").val(response);
  });
});

// Pretty print JSON
$("#pretty").click(function(){
  const jsonData = $("#json").val();
  try {
    const obj = JSON.parse(jsonData);
    const pretty = JSON.stringify(obj, null, 4); // indent 4 spazi
    $("#json").val(pretty);
    $("#validationResult").text("✅ JSON formattato").css("color", "green");
  } catch (e) {
    $("#validationResult").text("❌ JSON non valido: " + e.message).css("color", "red");
  }
});

      // Valida JSON
      $("#validate").click(function(){
        const jsonData = $("#json").val();
        try {
          JSON.parse(jsonData);
          $("#validationResult").text("✅ JSON valido").css("color", "green");
        } catch (e) {
          $("#validationResult").text("❌ JSON non valido: " + e.message).css("color", "red");
        }
      });

      // Salva JSON in localStorage
      $("#save-json").click(function(){
        const jsonData = $("#json").val();
        try {
          localStorage.setItem(KEY, jsonData);
          $("#saveStatus").text("✅ JSON salvato in localStorage").css("color", "green");
        } catch (e) {
          $("#saveStatus").text("❌ Errore salvataggio: " + e.message).css("color", "red");
        }
      });

      // Clear JSON
      $("#clear-left").click(function(){
        $("#json").val("");
        $("#validationResult").text("");
      });

      // Clear Struct
      $("#clear-right").click(function(){
        $("#struct").val("");
      });
    });
  </script>

Save the include file as "json_keys.h".
<br></br>
Save the following code on a file "main.c".
<pre>
#include &lt;stdio.h&gt;

#include "json_keys.h" 

int main() {
    struct Root_s root;
    printf("sizeof(Root_s) = %zu\n", sizeof(struct Root_s));
    return 0;
}
</pre>

Compile it with the following command:
<pre>
gcc main.c -o demo
</pre>

Execute it:
<pre>
./demo
</pre>
</body>
</html>
