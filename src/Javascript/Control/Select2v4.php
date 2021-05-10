<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Javascript\Control;


use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Ingenerator\BehatSupport\Assertion\Spin;
use Ingenerator\BehatSupport\Mink\MinkBrowserChecker;
use const JSON_PRETTY_PRINT;

/**
 * Helper for the 4.x series of select2/select2
 *
 * @package test\behat\support
 */
class Select2v4
{

    /**
     * @var WebAssert
     */
    protected $assert;

    /**
     * @var NodeElement
     */
    protected $select;

    /**
     * @var Session
     */
    protected $session;

    protected function __construct(Session $session, NodeElement $base_select)
    {
        MinkBrowserChecker::requireJavascript($session);

        if ( ! $base_select->hasClass('select2-hidden-accessible')) {
            throw new \UnexpectedValueException(
                'Control '.$base_select->getOuterHtml().' has not been initialised as select2'
            );
        }
        $this->assert  = new WebAssert($session);
        $this->session = $session;
        $this->select  = $base_select;
    }

    /**
     * Find a control based on the control's label text.
     *
     *   $control = Select2v4::fromLabel($session, 'Choose Country');
     *
     * @param Session          $session
     * @param string           $label
     * @param NodeElement|NULL $container
     *
     * @return static
     */
    public static function fromLabel(Session $session, $label, NodeElement $container = NULL)
    {
        $select = (new WebAssert($session))->fieldExists($label, $container);

        return new static($session, $select);
    }

    /**
     * @param array $captions
     */
    public function assertOnlyHasUnderlyingOptionsMatching(array $captions)
    {
        $found   = [];
        $options = $this->listCurrentUnderlyingOptions();
        foreach ($captions as $caption) {
            foreach ($options as $index => $option) {
                if (\preg_match('/'.\preg_quote($caption).'/', $option)) {
                    unset($options[$index]);
                    $found[$caption] = $option;
                    break;
                }
            }
        }
        $diff = \array_filter(
            [
                'missing' => \array_diff($captions, \array_keys($found)),
                'extra'   => $options,
            ]
        );
        if ($diff) {
            throw new \UnexpectedValueException(
                "Available underlying <option> values do not match:\n".\json_encode(
                    $diff,
                    JSON_PRETTY_PRINT
                )
            );
        }
    }

    /**
     * Wait for suggestions, then select the value with the given result caption
     *
     * @param string $result_caption
     */
    public function chooseResult($result_caption)
    {
        Spin::fn(
            function () use ($result_caption) {
                $choices = $this->listSearchSuggestions();
                if ( ! isset($choices[$result_caption])) {
                    throw new ExpectationException(
                        'Could not find a select 2 choice `'.$result_caption.'` in '.\json_encode(
                            \array_keys($choices)
                        ),
                        $this->session->getDriver()
                    );
                }
                $choices[$result_caption]->click();
            }
        )
            ->setDelayMs(500)
            ->forAttempts(10);
    }

    /**
     * The text values of the selections displayed in the control
     *
     * @return string[]
     */
    public function getRenderedSelections()
    {
        return \array_keys($this->listRenderedSelections($this->getComboBox()));
    }

    /**
     * The text values of the suggestions displayed in the search box
     *
     * @return string[]
     */
    public function getRenderedSuggestions()
    {
        return \array_keys($this->listSearchSuggestions());
    }

    /**
     * @return string[] as key => value
     */
    public function getSelectedOptions()
    {
        $results = [];
        foreach ($this->select->findAll('css', 'option') as $option) {
            /** @var NodeElement $option */
            if ($option->isSelected()) {
                $results[$option->getValue()] = \trim($option->getText());
            }
        }

        return $results;
    }

    /**
     * @return string[] as key => value
     */
    public function listCurrentUnderlyingOptions()
    {
        $results = [];
        foreach ($this->select->findAll('css', 'option') as $option) {
            /** @var NodeElement $option */
            $results[$option->getValue()] = \trim($option->getText());
        }

        return $results;
    }

    /**
     * Remove a selected value from a multi-select by matching on the caption
     *
     * @param string $caption
     */
    public function removeSelection($caption)
    {
        $combo = $this->getComboBox();
        if ( ! $combo->hasClass('select2-selection--multiple')) {
            throw new \BadMethodCallException(
                'Can only '.__METHOD__.' on a select2-selection--multiple - got '.$combo->getAttribute('class')
            );
        }
        $selections = $this->listRenderedSelections($combo);
        if ( ! isset($selections[$caption])) {
            throw new \InvalidArgumentException(
                'No selection `'.$caption.'` in '.\json_encode(\array_keys($selections))
            );
        }

        $this->assert->elementExists('css', '.select2-selection__choice__remove', $selections[$caption])
            ->click();
    }

    /**
     * @param string $term
     */
    public function selectOnlyChoiceMatching($term)
    {
        $this->typeSearchTerm($term);
        $this->waitForSuggestions();
        $suggestions = $this->getRenderedSuggestions();
        if (\count($suggestions) !== 1) {
            throw new \UnexpectedValueException(
                \sprintf(
                    "Expected exactly one choice matching `%s`, got:\n%s",
                    $term,
                    \json_encode($suggestions, JSON_PRETTY_PRINT)
                )
            );
        }
        $this->chooseResult(\array_shift($suggestions));
    }

    /**
     * Type a query, wait for suggestions, then select the suggestion matching $result
     *
     * @param string $term   partial search query
     * @param string $result text of the result to choose
     */
    public function typeAndChoose($term, $result)
    {
        $this->typeSearchTerm($term);
        $this->chooseResult($result);
    }

    /**
     * Just type a query into the control (don't wait for suggestions)
     *
     * @param string $term
     */
    public function typeSearchTerm($term)
    {
        $this->openSelect2();
        $driver       = $this->session->getDriver();
        $active_input = Spin::fn(
            function () use ($driver) {
                return $driver->evaluateScript('return document.activeElement.attributes["aria-controls"].value');
            }
        )
            ->setDelayMs(5)
            ->forAttempts(10);
        $this->session->getDriver()->setValue('//input[@aria-controls="'.$active_input.'"]', $term);
    }

    /**
     * Wait for the control to finish loading suggestions after a search
     */
    public function waitForSuggestions()
    {
        Spin::fn(
            function () {
                $this->assert->elementNotExists('css', '.select2-results__option.loading-results');

                // @todo: this is a workaround for https://github.com/select2/select2/issues/4355 which is fixed in v4.0.6
                // With that bug, select2 clears the "searching" option and gives "could not be loaded" on subsequent key presses
                $active_ajax = $this->session->evaluateScript('$.active;');
                if ($active_ajax) {
                    throw new \UnexpectedValueException('Ajax still running after search indicator cleared');
                }
            }
        )
            ->setDelayMs(200)
            ->forAttempts(40);
    }

    /**
     * @return NodeElement
     */
    protected function getComboBox()
    {
        $parent    = $this->select->getParent();
        $selection = $this->assert->elementExists('css', '.select2-selection', $parent);

        return $selection;
    }

    /**
     * @param NodeElement $combo
     *
     * @return NodeElement[]
     */
    protected function listRenderedSelections(NodeElement $combo)
    {
        if ($combo->hasClass('select2-selection--single')) {
            $choices = [$this->assert->elementExists('css', '.select2-selection__rendered', $combo)];
        } else {
            $choices = $combo->findAll('css', '.select2-selection__choice');
        }

        $selections = [];
        foreach ($choices as $choice) {
            /** @var NodeElement $choice */
            // Can't use getText as there's no guarantee these elements will be visible e.g. if control is hidden
            $text              = \trim(\strip_tags($choice->getHtml()), "× \t\n\r");
            $selections[$text] = $choice;
        }

        return $selections;
    }

    /**
     * @return NodeElement[]
     */
    protected function listSearchSuggestions()
    {
        $select_id  = $this->select->getAttribute('id');
        $results_id = 'select2-'.$select_id.'-results';
        $results    = $this->assert->elementExists('css', '#'.$results_id);
        $items      = [];
        foreach ($results->findAll('xpath', '//li[@aria-selected]') as $result) {
            /** @var NodeElement $result */
            $items[$result->getText()] = $result;
        }

        return $items;
    }

    protected function openSelect2()
    {
        $this->session->executeScript(
            \sprintf(
                "\$(%s).select2('open');",
                \json_encode('#'.$this->select->getAttribute('id'))
            )
        );

        // Wait for select2 to be open?
    }

}
