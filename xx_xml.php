<?php
/*
Source: http://de2.php.net/manual/en/function.xml-parse.php#83416

"This is a follow up to the parser class posted by neoyahuu at yahoo dot com. 
The xml_set_character_data_handler function falls prey to the weird splitting 
caused by special characters (i.e. new lines whenever an umlaut is found) - 
my fix just uses concatenation to stop this from happening. 
This is a great function otherwise. The code:"
*/
  
class xx_xml {

    // XML parser variables
    var $parser;
    var $name;
    var $attr;
    var $data  = array();
    var $stack = array();
    var $keys;
    var $path;
  
    // either you pass url atau contents.
    // Use 'url' or 'contents' for the parameter
    var $type;

    // function with the default parameter value
    function xx_xml($url='http://www.opocot.com', $type='url') {
        $this->type = $type;
        $this->url  = $url;
        $this->parse();
    }
  
    // parse XML data
    function parse()
    {
        $data = '';
        $this->parser = xml_parser_create ("UTF-8");
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'startXML', 'endXML');
        xml_set_character_data_handler($this->parser, 'charXML');

        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);

        if ($this->type == 'url') {
            // if use type = 'url' now we open the XML with fopen
          
            if (!($fp = @fopen($this->url, 'rb'))) {
                $this->error("Cannot open {$this->url}");
            }

            while (($data = fread($fp, 8192))) {
                if (!xml_parse($this->parser, $data, feof($fp))) {
                    $this->error(sprintf('XML error at line %d column %d',
                    xml_get_current_line_number($this->parser),
                    xml_get_current_column_number($this->parser)));
                }
            }
        } else if ($this->type == 'contents') {
            // Now we can pass the contents, maybe if you want
            // to use CURL, SOCK or other method.
            $lines = explode("\n",$this->url);
            foreach ($lines as $val) {
                if (trim($val) == '')
                    continue;
                $data = $val . "\n";
                if (!xml_parse($this->parser, $data)) {
                    echo $data.'<br />';
                    $this->error(sprintf('XML error at line %d column %d',
                    xml_get_current_line_number($this->parser),
                    xml_get_current_column_number($this->parser)));
                }
            }
        }
    }

    function startXML($parser, $name, $attr)    {
        $this->stack[$name] = array();
        $keys = '';
        $total = count($this->stack)-1;
        $i=0;
        foreach ($this->stack as $key => $val)    {
            if (count($this->stack) > 1) {
                if ($total == $i)
                    $keys .= $key;
                else
                    $keys .= $key . '|'; // The saparator
            }
            else
                $keys .= $key;
            $i++;
        }
        if (array_key_exists($keys, $this->data))    {
            $this->data[$keys][] = $attr;
        }    else
            $this->data[$keys] = $attr;
        $this->keys = $keys;
    }

    function endXML($parser, $name)    {
        end($this->stack);
        if (key($this->stack) == $name)
            array_pop($this->stack);
    }

    function charXML($parser, $data)    {
        if (trim($data) != '')
            @$startFrom = count($this->data[$this->keys])-1; // fixes weird splitting (bug?)
            @$startFrom = $startFrom == -1 ? $startFrom = 0 : $startFrom;
            @$this->data[$this->keys]['data'][$startFrom] .= trim(str_replace("\n", '', $data));
    }

    function error($msg)    {
        echo "<div align=\"center\">
            <font color=\"red\"><b>Error: $msg</b></font>
            </div>";
        exit();
    }
}

?>