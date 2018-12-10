<?php
/**
 * DokuWiki Plugin sqlquery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Pirogov <i1557@yandex.ru>
 * @author  Thomas Hooge <hooge@rowa-group.com>
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_sqlquery extends DokuWiki_Syntax_Plugin {

    public function getType() { return 'substition'; }
    public function getSort() { return 666; }
    public function getPType() { return 'block'; }

    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<sql\b(?:\s+(?:host|db)=[\w\-\.$]+?)*\s*>(?:.*?</sql>)', $mode, 'plugin_sqlquery');
    }

    /**
     * Handle matches of the sqlquery syntax
     *
     * @param string          $match   The match of the syntax
     * @param int             $state   The state of the handler
     * @param int             $pos     The position in the document
     * @param Doku_Handler    $handler The handler
     *
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        $data = array('state' => $state);
        if ($state == DOKU_LEXER_SPECIAL) {
            # get host
            if (preg_match('/<sql\b.*host=([\w\-\.$]+)/', $match, $result)) {
                $data['host'] = $result[1];
            } else {
                $data['host'] = $this->getConf('host');
            }
            # get database
            if (preg_match('/<sql\b.*db=([\w\-\.$]+)/', $match, $result)) {
                $data['db'] = $result[1];
            } else {
                $data['db'] = $this->getConf('db');
            }
            # get query
                $data['match'] = $match;
            if (preg_match('%<sql.*?>(.*)</sql>%s', $match, $result)) {
                $data['query'] = trim($result[1]);
            }
        }
        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode != 'xhtml') return false;
        if (empty($data['query'])) return true;

        // get configuration
        $user     = $this->getConf('user');
        $password = $this->getConf('password');

        // connect to database
        $link = mysqli_connect($data['host'], $user, $password, $data['db']);
        if (!$link) {
            $renderer->doc .= "<pre>" . mysqli_connect_error() . "</pre>";
            return true;
        }
        mysqli_set_charset($link, "utf8");

        // run query
        $result = mysqli_query($link, $data['query']);
        if ($result) {

            // get the number of fields in the table
            $fieldcount = mysqli_num_fields($result);

            // build a table
            $renderer->doc .= '<table id="sqlquerytable" class="inline">' . "\n";

            // build the header section of the table
            $renderer->doc .= "<thead><tr>";
            while ($fieldinfo = mysqli_fetch_field($result)) {
                $renderer->doc .= "<th>";
                $renderer->doc .= $fieldinfo->name;
                $renderer->doc .= "</th>";
            }
            $renderer->doc .= "</tr></thead>\n";

            // build the contents of the table
            $renderer->doc .= "<tbody>\n";
            while ($row = mysqli_fetch_row($result)) {
                  $renderer->doc .= "<tr>";
                  for ( $i = 0; $i < $fieldcount; $i++ ) {
                      $renderer->doc .= "<td>";
                      $renderer->doc .= $row[$i];
                      $renderer->doc .= "</td>";
                  }
                  $renderer->doc .= "</tr>\n";
            }

            // finish the table
            $renderer->doc .= "</tbody></table>\n";

        } else {
            // error in query
            $renderer->doc .= "<pre>" . mysqli_error($link) . "</pre>";
        }

        mysqli_close($link);

        return true;
    }
}
