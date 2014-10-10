
<?php

$arguments = arguments($argv);

require 'vendor/autoload.php';



// Load arguments passed from CLI 

if(empty($arguments['filename'])) {
    echo "No input file specified. Use --filename=mediawiki.xml" . PHP_EOL . PHP_EOL; 
    exit;
}

if(!empty($arguments['output'])) {
    $output_path = $arguments['output'];
        
    if(!file_exists($output_path)) {
        echo "Creating output directory $output_path" . PHP_EOL . PHP_EOL;
        mkdir($output_path);
    }

} else {
    $output_path = '';
}

if(!empty($arguments['format'])) {
    $format = $arguments['format'];
} else {
    $format = 'markdown_github';
}


if(!empty($arguments['fm']) OR (empty($arguments['fm']) && $format == 'markdown_github')) {
    $add_meta = true;
} else {
    $add_meta = false;
}




// Load XML file
$file = file_get_contents($arguments['filename']);

$xml = str_replace('xmlns=', 'ns=', $file); //$string is a string that contains xml... 

$xml = new SimpleXMLElement($xml);


$result = $xml->xpath('page');
$count = 0;

// Iterate through XML
while(list( , $node) = each($result)) {
    
    $title = $node->xpath('title');
    $url = $title[0];
    $url = str_replace(' ', '_', $url);

    if($slash = strpos($url, '/')){
        $directory = substr($url, 0, $slash);
        $filename = substr($url, $slash+1);
    } else {
        $directory = '';
        $filename = $url;
    }

    $text = $node->xpath('revision/text');
    $text = $text[0];
    $text = html_entity_decode($text);

    
    // prepare to append page title frontmatter to text
    if ($add_meta) {    
        $frontmatter = "---\n";
        $frontmatter .= "title: $filename\n";
        $frontmatter .= "permalink: $url\n";
        $frontmatter .= "---\n\n";
    }

    $pandoc = new Pandoc\Pandoc();
    $options = array(
        "from"  => "mediawiki",
        "to"    => $format
    );
    $text = $pandoc->runWith($text, $options);

    $text = str_replace('\_', '_', $text);

    if ($add_meta) {
        $text = $frontmatter . $text;
    }

    if (substr($output_path, -1) != '/') $output_path = $output_path . '/';

    $directory = $output_path . $directory;

    // create directory if necessary
    if(!empty($directory)) {
        if(!file_exists($directory)) {
            mkdir($directory);
        }

        $directory = $directory . '/';
    }

    // create file

    $file = fopen($directory . $filename . '.md', 'w');
    fwrite($file, $text);
    fclose($file);

    $count++;

}

if ($count > 0) {
    echo "$count files converted" . PHP_EOL . PHP_EOL;
}


function arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
      if (preg_match('/--([^=]+)=(.*)/',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(preg_match('/-([a-zA-Z0-9])/',$arg,$reg)) {
            $_ARG[$reg[1]] = 'true';
        }
  
    }
  return $_ARG;
}



?>