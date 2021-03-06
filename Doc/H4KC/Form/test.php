<?php

/* test.dot */

class QNA {

    var $file_name = 'test.dot';

    var $stack = array();

    var $stack_i = -1;
    var $type_stack = array();

    var $pages = array();

    var $tab_stack = array();                                                       // where we store the content of the tabs
    var $tabs = array();                                                            // meta data about the tabs

    var $tab_group = 0;                                                             // indexs into $tab_stack and $tabs
    var $tab_number = 0;

    public function __construct() {
        $this->fh = fopen($this->file_name, "r") or die("Unable to open file!");

        while ( $line = fgets($this->fh)) {



            if ($s = preg_match("/\.([a-z]*)\s(.*)|(.*)/", $line, $field)) {

                if (!empty($field[1])) {

                    $field_name = $field[1];
                    $field_value = $field[2];

                    switch ( $field_name ) {
                        case 'page':

                            if ($this->stack_i != -1 ) {
                                $this->end_page();
                            }


                            $this->start_page($field_value);

                            break;


                        case 'tab':


                            if  ($this->type_stack[$this->stack_i] != 'tab') {       // If we are not in a tab group create it
                                $this->start_tab_group();
                            } else {
                                $this->end_tab();
                            }
                            $this->start_tab($field_value);                                                 // Then start a tab
                            break;
    
                        default:
                            print_r($field);
                            break;

                    }

                } else {
                    $this->stack[$this->stack_i][] = $field[0];
                }
            } else {
            print "2- $line \n";
                $this->stack[$this->stack_i][] = $field[0];
            }

        }
        if ($this->stack_i != -1 ) {                // Print the current page if there is one.
            $this->end_page();
        }

    }

    function start_page($field) {
        $this->stack_i++;
      //  $this->stack[$this->stack_i] = '';
        $this->type_stack[$this->stack_i] = 'page';


        list( $page_id, $page_title) = $this->get_page_name_description($field);

        $this->stack[$this->stack_i][] = "<div id=\"$page_id\" class=\"page\">\n";

        if ( !empty( $page_title) ) {
            $this->stack[$this->stack_i][] = "<h1>$page_title</h1>\n";
        }
    }

    function end_page() {

        if ( $this->type_stack[$this->stack_i] != 'page' ) {
            $this->end_tab_group();
        }


        foreach ( $this->stack[$this->stack_i] AS $i => $line ) {
            if ( $i > 0 ) print "\t";
            print $line . "\n";
        }

        print '</div>' . "\n";

        unset($this->stack[$this->stack_i]);
        unset($this->type_stack[$this->stack_i]);
        $this->stack_i--;


    }

    function start_tab_group() {

        $this->tab_group++;
        $this->stack_i++;

        $this->type_stack[$this->stack_i] = 'tab-group';
      //  $this->stack[$this->stack_i][] = '  TAB GROUP START';
    }

    function end_tab_group() {

        $this->end_tab();

        $this->stack[$this->stack_i -1][] = "<div>\n\t<ul class=\"nav nav-tabs\" role=\"tablist\">\n";

        foreach ( $this->tabs[$this->tab_group] AS $tab_i => $tab ) {

            $id = $tab['id'];
            $title = $tab['title'];
            $this->stack[$this->stack_i -1][] = "\t\t<li role=\"presentation\" class=\"active\"><a href=\"#$id\" aria-controls=\"$id\" role=\"tab\" data-toggle=\"tab\">$title</a></li>\n";
        }

        $this->stack[$this->stack_i -1][] = "<\ul>\n";


        $this->stack[$this->stack_i -1][] = "<div class=\"tab-content\">\n";

        foreach ( $this->tabs[$this->tab_group] AS $tab_number => $tab ) {

            $id = $tab['id'];
            $title = $tab['title'];
            $this->stack[$this->stack_i -1][] = "\t<div role=\"tabpanel\" class=\"tab-pane active\" id=\"$id\">\n";

            foreach ($this->tab_stack[$this->tab_group][$tab_number] AS $line) {
                $this->stack[$this->stack_i -1][] = "\t\t$line";
            }

            $this->stack[$this->stack_i -1][] = "\t<\div>\n";
        }


        $this->stack[$this->stack_i -1][] = "<\div>\n";

        $this->stack_i--;

    }

    function start_tab($field) {
        $this->tab_number++;
        $this->stack_i++;

        $this->type_stack[$this->stack_i] = 'tab';
    //    $this->stack[$this->stack_i][] = '  TAB START';

        list( $tab_id, $tab_title) = $this->get_tab_name_description($field);

        $this->tabs[$this->tab_group][$this->tab_number] = array('id' => $tab_id, 'title' => $tab_title);


    }

    function end_tab() {
  //      $this->stack[$this->stack_i][] = '  TAB END';
        foreach ( $this->stack[$this->stack_i] AS $i => $line ) {
            if ( $i > 0 ) $line =  "\t\t" . $line;
            $this->tab_stack[$this->tab_group][$this->tab_number][] = "\t" . $line;
        }
        unset($this->stack[$this->stack_i]);
        unset($this->type_stack[$this->stack_i]);
        $this->stack_i--;


    }

    function get_page_name_description($field) {

        $page_no = sizeof( $this->pages );
        $page_id = 'page_' . $page_no;
        $page_title = '';

        if ( !empty ($field) ) {

            $ret = explode('|',$field, 2);

            if ( sizeof ($ret) == 1 ) {
                $page_title = $ret[0];
            } else {
                $page_id = $ret[0];
                $page_title = $ret[1];
            }

        }

        $this->pages[] = array( 'id' => $page_id, 'title' => $page_title);

        return array($page_id, $page_title);
    }

    function get_tab_name_description($field) {

        $tab_no = sizeof( $this->tabs );
        $tab_id = 'tab_' . $tab_no;
        $tab_title = '';

        if ( !empty ($field) ) {

            $ret = explode('|',$field, 2);

            if ( sizeof ($ret) == 1 ) {
                $tab_title = $ret[0];
            } else {
                $tab_id = $ret[0];
                $tab_title = $ret[1];
            }

        }

        $this->tabs[] = array( 'id' => $tab_id, 'title' => $tab_title);

        return array($tab_id, $tab_title);
    }


}

$qna = new QNA();


