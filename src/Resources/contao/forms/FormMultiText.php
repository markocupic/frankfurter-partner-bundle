<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Class FormSelectMenu
 *
 * @property integer $mSize
 * @property boolean $mandatory
 * @property boolean $multiple
 * @property array $options
 * @property boolean $chosen
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormMultiText extends \Widget
{

    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Add a for attribute
     *
     * @var boolean
     */
    protected $blnForAttribute = true;

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_multi_text';

    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-multi-text';

    /**
     * Add specific attributes
     *
     * @param string $strKey The attribute name
     * @param mixed $varValue The attribute value
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey)
        {
            case 'varValue':
            case 'value':
                $this->varValue = \StringUtil::deserialize($varValue);
                $this->arrOptions = \StringUtil::deserialize($varValue);
                break;
                break;
            case 'mandatory':
                if ($varValue)
                {
                    $this->arrAttributes['required'] = 'required';
                }
                else
                {
                    unset($this->arrAttributes['required']);
                }
                parent::__set($strKey, $varValue);
                break;

            case 'mSize':
                // Ignore
                break;

            case 'multiple':
                // Ignore
                break;

            case 'options':
                $this->arrOptions = \StringUtil::deserialize($varValue);
                break;

            case 'rgxp':
            case 'minlength':
            case 'maxlength':
                // Ignore
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    /**
     * Check options if the field is mandatory
     */
    public function validate()
    {
        $mandatory = $this->mandatory;
        $options = $this->getPost($this->strName);

        // Remove empty values
        $options = array_filter($options);

        // Check if there is at least one value
        if ($mandatory && \is_array($options))
        {
            foreach ($options as $option)
            {
                if (\strlen($option))
                {
                    $this->mandatory = false;
                    break;
                }
            }
        }

        $varInput = $this->validator($options);

        // Check for a valid option (see #4383)
        if (!empty($varInput) && !$this->isValidOption($varInput))
        {
            //$this->addError($GLOBALS['TL_LANG']['ERR']['invalid']);
        }

        // Add class "error"
        if ($this->hasErrors())
        {
            $this->class = 'error';
        }
        else
        {
            $this->varValue = $varInput;
            $this->options = $varInput;
        }

        // Reset the property
        if ($mandatory)
        {
            $this->mandatory = true;
        }
    }

    /**
     * Return a parameter
     *
     * @param string $strKey The parameter name
     *
     * @return mixed The parameter value
     */
    public function __get($strKey)
    {

        if ($strKey == 'options')
        {
            return $this->arrOptions;
        }
        if ($strKey == 'value')
        {
            return $this->arrOptions;
        }
        if ($strKey == 'varValue')
        {
            return $this->arrOptions;
        }
        return parent::__get($strKey);
    }

    /**
     * Parse the template file and return it as string
     *
     * @param array $arrAttributes An optional attributes array
     *
     * @return string The template markup
     */
    public function parse($arrAttributes = null)
    {
        $strClass = 'multi-text';

        // Custom class
        if ($this->strClass != '')
        {
            $strClass .= ' ' . $this->strClass;
        }

        $this->strClass = $strClass;

        return parent::parse($arrAttributes);
    }

    /**
     * Generate the options
     *
     * @return array The options array
     */
    protected function getOptions()
    {
        $arrOptions = array();
        $blnHasGroups = false;

        // Add empty option if there are none
        if (empty($this->arrOptions) || !\is_array($this->arrOptions))
        {
            $this->arrOptions = array('');
        }

        // Generate options
        foreach ($this->arrOptions as $value)
        {
            $arrOptions[] = array
            (
                'type'  => 'option',
                'value' => $value,
            );
        }


        return $arrOptions;
    }

    /**
     * Generate the widget and return it as string
     *
     * @return string The widget markup
     */
    public function generate()
    {
        $strOptions = '';

        // Add empty option if there are none
        if (empty($this->arrOptions) || !\is_array($this->arrOptions))
        {
            $this->arrOptions = array(array('type' => 'option', 'value' => ''));
        }

        foreach ($this->arrOptions as $arrOption)
        {


            $strOptions .= sprintf('<option value="%s"%s>%s</option>',
                $arrOption['value'],
                $this->isSelected($arrOption),
                $arrOption['label']);
        }


        return sprintf('<select name="%s" id="ctrl_%s" class="%s"%s>%s</select>',
            $this->strName,
            $this->strId,
            $this->class,
            $this->getAttributes(),
            $strOptions);
    }
}
