<?php
/**
 * DokuWiki Plugin sqlquery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Pirogov <i1557@yandex.ru>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_sqlquery extends DokuWiki_Syntax_Plugin {

    public function getType() {
        return 'substition';
    }

    public function getSort() {
        return 666;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<sql>', $mode, 'plugin_sqlquery');
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</sql>','plugin_sqlquery');
    }

    /**
     * Handle matches of the sqlquery syntax
     *
     * @param string          $match   The match of the syntax
     * @param int             $state   The state of the handler
     * @param int             $pos     The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ( $state )
        {
              case DOKU_LEXER_ENTER:
              $data = array();
              return $data;
              break;

              case DOKU_LEXER_UNMATCHED:
        			return array('sqlquery' => $match);
              break;

              case DOKU_LEXER_EXIT:
              $data = array();
              return $data;
              break;

        }

        $data = array();
        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ( $mode != 'xhtml' ) return false;

        if ( !empty( $data['sqlquery'] ) )
        {
            // получаем параметры конфигурации
            $host     = $this->getConf('Host');
            $DB       = $this->getConf('DB');
            $user     = $this->getConf('user');
            $password = $this->getConf('password');

            // получаем запрос
            $querystring = $data['sqlquery'];

            // подключаемся к базе
            $link = mysqli_connect($host, $user, $password, $DB);
            mysqli_set_charset($link, "utf8");

            // подключились
            if ( $link )
            {
                $result = mysqli_query($link, $querystring);
                if ( $result )
                {
                    // получаем кол-во полей в таблице
                    $fieldcount = mysqli_num_fields($result);

                    // строим таблицу
                    $renderer->doc .= "<table id=\"sqlquerytable\" class=\"inline\">";

                    // строим заголовок
                    $renderer->doc .= "<thead><tr>";
                    while ($fieldinfo = mysqli_fetch_field($result))
                    {
                        $renderer->doc .= "<th>";
                        $renderer->doc .= $fieldinfo->name;
                        $renderer->doc .= "</th>";
                    }
                    $renderer->doc .= "</tr></thead>";

                    // строим содержимое таблицы
                    $renderer->doc .= "<tbody>";
                    while ($row = mysqli_fetch_row($result))
                    {
                          $renderer->doc .= "<tr>";

                          // строим строку
                          for ( $i = 0; $i < $fieldcount; $i++ )
                          {
                              $renderer->doc .= "<td>";
                              $renderer->doc .= $row[$i];
                              $renderer->doc .= "</td>";
                          }
                          $renderer->doc .= "</tr>";
                    } // of while fetch_row
                    // закрываем таблицу
                    $renderer->doc .= "</tbody></table>";
                } // of mysqli_query
                mysqli_close($link);
            } // of mysqli link
        } // of sqlquery not empty
        return true;
    } // of render function
}

// vim:ts=4:sw=4:et:
