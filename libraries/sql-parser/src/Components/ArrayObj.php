<?php

/**
 * Parses an array.
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses an array.
 *
 * @category   Components
 * @package    SqlParser
 * @subpackage Components
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class ArrayObj extends Component
{

    /**
     * The array that contains the unprocessed value of each token.
     *
     * @var array
     */
    public $raw = array();

    /**
     * The array that contains the processed value of each token.
     *
     * @var array
     */
    public $values = array();

    /**
     * Constructor.
     *
     * @param array $raw    The unprocessed values.
     * @param array $values The processed values.
     */
    public function __construct(array $raw = array(), array $values = array())
    {
        $this->raw = $raw;
        $this->values = $values;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return ArrayObj
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new ArrayObj();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 ------------------[ array element ]-----------------> 2
         *
         *      2 ------------------------[ , ]-----------------------> 1
         *      2 ------------------------[ ) ]-----------------------> -1
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             * @var Token $token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if (($token->type !== Token::TYPE_OPERATOR) || ($token->value !== '(')) {
                    $parser->error(
                        __('An opening bracket was expected.'),
                        $token
                    );
                    break;
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                    // Empty array.
                    break;
                }
                $ret->values[] = $token->value;
                $ret->raw[] = $token->token;
                $state = 2;
            } elseif ($state === 2) {
                if (($token->type !== Token::TYPE_OPERATOR) || (($token->value !== ',') && ($token->value !== ')'))) {
                    $parser->error(
                        __('A comma or a closing bracket was expected'),
                        $token
                    );
                    break;
                }
                if ($token->value === ',') {
                    $state = 1;
                } else { // )
                    break;
                }
            }

        }

        return $ret;
    }

    /**
     * @param ArrayObj $component The component to be built.
     *
     * @return string
     */
    public static function build($component)
    {
        $values = array();
        if (!empty($component->raw)) {
            $values = $component->raw;
        } else {
            foreach ($component->values as $value) {
                $values[] = $value;
            }
        }
        return '(' . implode(', ', $values) . ')';
    }
}
