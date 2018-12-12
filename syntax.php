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
        $this->Lexer->addSpecialPattern('<sql\b(?:\s+(?:host|db|type)=[\w\-\.$]+?)*\s*>(?:.*?</sql>)', $mode, 'plugin_sqlquery');
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
            # get type (DSN prefix)
            if (preg_match('/<sql\b.*type=(mysql|dblib)/', $match, $result)) {
                $data['type'] = $result[1];
            } else {
                $data['type'] = $this->getConf('type');
            }
            # get host
            if (preg_match('/<sql\b.*host=([\w\-\.$]+)/', $match, $result)) {
                $data['host'] = $result[1];
            } else {
                $data['host'] = $this->getConf('Host');
            }
            # get database
            if (preg_match('/<sql\b.*db=([\w\-\.$]+)/', $match, $result)) {
                $data['db'] = $result[1];
            } else {
                $data['db'] = $this->getConf('DB');
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
        $dsn = "{$data['type']}:host={$data['host']};dbname={$data[db]};charset=UTF-8;";
        try {
            $dbh = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            $renderer->doc .= "<pre>Unable to connect ro database:" . $e->getMessage() . "</pre>\n";
            return true;
        }
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NUM);

        // run query
        try {
            $result = $dbh->query($data['query']);
        } catch (PDOException $e) {
            $renderer->doc .= "<pre>Error in query:" . $e->getMessage() . "</pre>\n";
            return true;
        }

        // get the number of fields in the table
        $fieldcount = $result->columnCount();

        // build a table
        $renderer->doc .= '<table id="sqlquerytable" class="inline">' . "\n";

        // build the header section of the table
        $renderer->doc .= "<thead><tr>";

        for ($i = 0; $i < $fieldcount; $i++) {
            $meta = $result->getColumnMeta($i);
            $renderer->doc .= "<th>";
            $renderer->doc .= $meta['name'];
            $renderer->doc .= "</th>";
        }
        $renderer->doc .= "</tr></thead>\n";

        // build the contents of the table
        $renderer->doc .= "<tbody>\n";
        foreach ($result as $row) {
            $renderer->doc .= "<tr>";
            for ( $i = 0; $i < $fieldcount; $i++ ) {
                $renderer->doc .= "<td>";
                $renderer->doc .= htmlentities($row[$i]);
                $renderer->doc .= "</td>";
            }
            $renderer->doc .= "</tr>\n";
        }

        // finish the table
        $renderer->doc .= "</tbody>\n</table>\n";

        // Close connection, there is no close() method with PDO :-(
        $result = null;
        $dbh = null;

        return true;
    }
}
