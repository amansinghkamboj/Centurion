<?php
/**
 * Centurion
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@centurion-project.org so we can send you a copy immediately.
 *
 * @category    Centurion
 * @package     Centurion_Form
 * @subpackage  Model
 * @copyright   Copyright (c) 2008-2011 Octave & Octave (http://www.octaveoctave.com)
 * @license     http://centurion-project.org/license/new-bsd     New BSD License
 * @version     $Id$
 */

/**
 * @category    Centurion
 * @package     Centurion_Form
 * @subpackage  Model
 * @copyright   Copyright (c) 2008-2011 Octave & Octave (http://www.octaveoctave.com)
 * @license     http://centurion-project.org/license/new-bsd     New BSD License
 * @author      Florent Messa <florent.messa@gmail.com>
 * @author      Nicolas Duteil <nd@octaveoctave.com>
 * @author      Laurent Chenay <lchenay@gmail.com>
 */
abstract class Centurion_Form_Model_Abstract extends Centurion_Form
{
    const CSRF_HASH_KEY = 'Centurion';
    const REFERENCE_SUB_FORM = 'reference';
    const DEPENDENT_SUB_FORM = 'dependent';

    protected static $_specialSubForms = array(
        self::REFERENCE_SUB_FORM =>  '_referenceSubForms',
        self::DEPENDENT_SUB_FORM =>  '_dependentSubForms'
    );

    /**
     * Model linked to form.
     *
     * @var Centurion_Db_Table_Abstract
     */
    protected $_model = null;

    protected $_modelClassName = '';

    protected $_referenceSubForms = array();

    protected $_dependentSubForms = array();

    protected $_isNew = false;

    protected $_select = array();

    /**
     * Column types.
     *
     * @var array
     */
    protected $_columnTypes = array(
        'integer'       =>  'text',
        'int'           =>  'text',
        'smallint'      =>  'text',
        'bigint'        =>  'text',
        'decimal'       =>  'text',
        'float'         =>  'text',
        'float unsigned'=>  'text',
        'string'        =>  'text',
        'varchar'       =>  'text',
        'char'          =>  'text',
        'boolean'       =>  'checkbox',
        'timestamp'     =>  'datepicker',
        'time'          =>  'text',
        'date'          =>  'text',
        'enum'          =>  'select',
        'text'          =>  'textarea',
        'mediumtext'    =>  'textarea',
        'smalltext'     =>  'textarea',
        'longtext'      =>  'textarea'
    );

    /**
     * Column validators.
     *
     * @var array
     */
    protected $_columnValidators = array(
        'integer'       =>  'Int',
        'int'           =>  'Int',
        'smallint'      =>  'Int',
        'bigint'        =>  'Int',
        'decimal'       =>  'Float',
        'float'         =>  'Float',
        'float unsigned'=>  'Float',
        'string'        =>  'StringLength',
        'varchar'       =>  'StringLength',
        'char'          =>  'StringLength',
    );

    /**
     * Column filters.
     *
     * @var array
     */
    protected $_columnFilters = array(
        'text'          =>  array('StripTags'),
        'mediumtext'    =>  array('StripTags'),
        'smalltext'     =>  array('StripTags'),
        'longtext'      =>  array('StripTags')
    );

    /**
     * Model instance.
     *
     * @var Centurion_Db_Table_Row_Abstract
     */
    protected $_instance = null;

    /**
     * Excluded elements.
     *
     * @var array
     */
    protected $_exclude = array();

    /**
     * Disabled elements. Will be show but can not be edit.
     *
     * @var array
     */
    protected $_disable = array();

    /**
     * Label for elements.
     *
     * @var array
     */
    protected $_elementLabels = array();

    protected $_fields = null;

    protected $_values = null;

    /**
     * Constructor
     *
     * @param   array|Zend_Config           $options    Options
     * @param   Centurion_Db_Table_Row_Abstract $instance   Instance attached to the form
     * @return void
     */
    public function __construct($options, Centurion_Db_Table_Row_Abstract $instance = null)
    {
        if (!$this->_modelClassName || !is_string($this->_modelClassName)) {
            // @todo: below if statement is maintained for BC but it might be useless
            if (!$this->_model || !$this->_model instanceof Centurion_Db_Table_Abstract)
                throw new Centurion_Exception("Empty or invalid property _modelClassName");
        }

        Centurion_Traits_Common::initTraits($this);

        $this->addElementPrefixPath('Centurion_Form_Decorator',
                                    'Centurion/Form/Decorator/',
                                    'decorator');

        $this->_preGenerate();

        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }

        $this->addElements($this->_columnToElements());

        if (!defined('PHPUNIT') || PHPUNIT == false)
            $this->addElement('Hash', '_XSRF', array('salt' => $this->getAttrib('id')));

        // Extensions...
        $this->init();

        $this->loadDefaultDecorators();

        $this->setInstance($instance);
        $this->_postGenerate();

    }
    
    public function __wakeup()
    {
        if (null !== $this->getElement('_XSRF')) {
            $this->removeElement('_XSRF');
            $this->addElement('Hash', '_XSRF', array('salt' => $this->getAttrib('id')));
        }
    }

//    public function __call($function, $args)
//    {
//
//    }

    /**
     * Retrieve instance of model form.
     *
     * @return Centurion_Db_Table_Abstract
     */
    public function getInstance()
    {
        return $this->_instance;
    }

    /**
     * Set form state from options array.
     *
     * @param  array $options
     * @return Centurion_Form_Model_Abstract
     */

    public function setOptions(array $options)
    {
        if (isset($options['model']))
            $this->_model = $options['model'];

        if (isset($options['modelClassName']))
            $this->_modelClassName = $options['modelClassName'];

        if (isset($options['exclude']))
            $this->setExclude($options['exclude']);

        if (isset($options['fields']))
            $this->setFields($options['fields']);

        if (isset($options['columnTypes']))
            $this->setColumnTypes($options['columnTypes']);

        if (isset($options['elementLabels']))
            $this->setElementLabels($options['elementLabels']);

        return parent::setOptions($options);
    }

    /**
     * Set excluded elements.
     *
     * @param array $exclude
     */
    public function setExclude(array $exclude)
    {
        $this->_exclude = $exclude;

        return $this;
    }

    /**
     * Set excluded elements.
     *
     * @param array $exclude
     */
    public function addExclude($exclude)
    {
        $this->_exclude[] = $exclude;

        return $this;
    }

    /**
     * Set fields.
     *
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->_fields = $fields;

        return $this;
    }

    /**
     * Set column types.
     *
     * @param array $columnTypes
     */
    public function setColumnTypes(array $columnTypes)
    {
        $this->_columnTypes = $columnTypes;

        return $this;
    }

    /**
     * Set labels for elements.
     *
     * @param array $elementLabels
     */
    public function setElementLabels(array $elementLabels)
    {
        $this->_elementLabels = $elementLabels;

        return $this;
    }

    /**
     * Set instance.
     *
     * @param Centurion_Db_Table_Row_Abstract $instance
     * @return Centurion_Form_Model_Abstract
     */
    public function setInstance(Centurion_Db_Table_Row_Abstract $instance = null)
    {
        $this->_instance = $instance;
        if (null !== $instance) {
            $this->populate($instance->toArray());

            $this->setSubInstance()
                 ->_populateManyDependentTables($instance)
                 ->_onPopulateWithInstance();
        }

        return $this;
    }

    public function setSubInstance()
    {
        $parentReferenceMap = $this->getModel()->getReferenceMap();
        foreach ($this->getSubForms() as $form) {
            if (array_key_exists($form->getName(), $parentReferenceMap)) {
                $form->setInstance($this->_instance->{$form->getName()});
            }
        }

        return $this;
    }

    public function populateWithInstance(Centurion_Db_Table_Row_Abstract $instance = null)
    {
        if (null !== $instance) {
            $this->populate($instance->toArray());

            $parentReferenceMap = $this->getModel()->getReferenceMap();
            foreach ($this->getSubForms() as $form) {
                if (array_key_exists($form->getName(), $parentReferenceMap)) {
                    $form->populateWithInstance($instance->{$form->getName()});
                }
            }
            $this->_populateManyDependentTables($instance)
                 ->_onPopulateWithInstance();
        }

        return $this;
    }

    /**
     *
     * @return boolean False if there is no instance, otherwise, true
     */
    public function hasInstance()
    {
        return null !== $this->_instance;
    }

    /**
     *
     * @return Centurion_Db_Table_Abstract
     */
    public function getModel()
    {
        if (!$this->_model instanceof Centurion_Db_Table_Abstract)
            $this->_model = Centurion_Db::getSingletonByClassName($this->_modelClassName);

        return $this->_model;
    }

    public function setModel(Centurion_Db_Table_Abstract $model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Save the form and attached model.
     *
     * @todo implement multiple database
     * @return Centurion_Db_Table_Abstract
     */
    public function save($adapter = null)
    {
        if (null === $adapter) {
            $adapter = $this->getModel()->getAdapter();
        }

        try {
            $adapter->beginTransaction();

            $this->_doSave();

            $adapter->commit();
        } catch (Exception $e) {
            $adapter->rollback();
            throw $e;
        }

        return $this->_instance;
    }

    public function saveInstance($values = null)
    {
        if ($values === null) {
            $values = $this->getValues();
        }

        $values = $this->_saveReferenceSubForms($this->_processValues($values));

        if (!$this->hasInstance()) {
            $this->_isNew = true;
            $this->_instance = $this->getModel()->createRow();
        } else {
        	$this->_instance->setReadOnly(false);
        }


        $this->_instance->setFromArray($values);

        $this->_preSave();
        Centurion_Signal::factory('pre_save')->send($this);

        $this->_instance->save();

        $this->_saveManyDependentTables();

        $this->_postSave($this->_isNew);

        Centurion_Signal::factory('post_save')->send($this);

        return $this->_instance;
    }

    public function getReferenceSubForm($name)
    {
        return $this->_getSpecialSubForm($name, self::REFERENCE_SUB_FORM);
    }

    public function getDependentSubForm($name)
    {
        return $this->_getSpecialSubForm($name, self::DEPENDENT_SUB_FORM);
    }

    public function getReferenceSubForms()
    {
        return $this->_getSpecialSubForms(self::REFERENCE_SUB_FORM);
    }

    public function getDependentSubForms()
    {
        return $this->_getSpecialSubForms(self::DEPENDENT_SUB_FORM);
    }

    public function addReferenceSubForm(Centurion_Form $form, $name, $order = null)
    {
        if (!array_key_exists($name, $this->getModel()->getReferenceMap())) {
            throw new Centurion_Form_Exception(sprintf("%s is not a referenceMap of %s", $name, get_class($this->getModel())));
        }

        return $this->_addSpecialSubForm($form->setIsArray(true), $name, $order, self::REFERENCE_SUB_FORM);
    }

    public function addDependentSubForm(Centurion_Form $form, $name, $order = null)
    {
        if (!array_key_exists($name, $this->getModel()->getDependentTables())) {
            throw new Centurion_Form_Exception(sprintf("%s is not a dependentTable of %s", $name, get_class($this->getModel())));
        }

        return $this->_addSpecialSubForm($form, $name, $order, self::DEPENDENT_SUB_FORM);
    }

    /**
     * Retrieve form values.
     *
     * @param   boolean $suppressArrayNotation
     * @return  array   Values.
     */
    public function getValues($suppressArrayNotation = false)
    {
        if (null !== $this->_values) {
            return $this->_values;
        }

        return $this->_cleanValues(parent::getValues($suppressArrayNotation));
    }

    public function setValues($values)
    {
        $this->_values = $values;

        return $this;
    }

    public function isExcluded($columnName)
    {
        if (in_array($columnName, $this->_exclude)) {
            return true;
        }

        if (null !== $this->_fields && !in_array($columnName, $this->_fields)) {
            return true;
        }

        return false;
    }

    public function isDisabled($columnName)
    {
        if (in_array($columnName, $this->_disable)) {
            return true;
        }

        return false;
    }

    public function enableElement($name)
    {
        $key = array_search($name, $this->_disable);
        if ($key !== false) {
            unset($this->_disable[$key]);

            if ($elmt = $this->getElement($name)) {
                if ($elmt instanceof Zend_Form)
                    $elmt->removeAttrib('disabled');
            }
        }
        return $this;
    }

    public function disableElement($name)
    {
        if (!$this->isDisabled($name)) {
            array_push($this->_disable, $name);

            if ($elmt = $this->getElement($name)) {
                $elmt->setAttrib('disabled', 'disabled');
            }
        }

        return $this;
    }

    public function setDisable(array $disable)
    {
        $this->_disable = $disable;

        return $this;
    }

    public function removeSubForm($name)
    {
        $removed = parent::removeSubForm($name);

        if ($removed) {
            foreach (self::$_specialSubForms as $specialSubFormType) {
                $keys = array_keys($this->{$specialSubFormType}, $name);
                if (count($keys)) {
                    foreach($keys as $key) {
                        unset($this->{$specialSubFormType}[$key]);
                    }
                    break;
                }
            }
        }
        return $removed;
    }

    /**
     * Test recursively if elements of the form (or its subform) are upload field and if one of those has upload something.
     *
     * @return bool;
     */
    public function isUploaded()
    {
        $result = false;
        $subforms = $this->getSubForms();
        foreach($subforms as $subform) {
            if ($subform instanceof Media_Form_Model_Admin_File) {
                $result = $result || $subform->getFilename()->isUploaded();
            } else {
                $subform->isUploaded();
            }
        }
        return $result;
    }

    public function isNew()
    {
        return $this->_isNew;
    }

    protected function _doSave($adapter = null)
    {
        if (null === $adapter) {
            $adapter = $this->getModel()->getAdapter();
        }

        $this->saveInstance();

        return $this;
    }

    protected function _saveReferenceSubForms($values = null)
    {
        if (null === $values) {
            $values = $this->getValues();
        }

        $parentReferenceMap = $this->getModel()->getReferenceMap();

        foreach ($this->getReferenceSubForms() as $form) {
            if (false === $form->getValues()) {
                continue;
            }

            $referenceMap = $parentReferenceMap[$form->getName()];

            $form->saveInstance($values[$form->getName()]);
            $instance = $form->getInstance();

            $values[$referenceMap['columns']] = $instance->{$referenceMap['refColumns']};
        }

        return $values;
    }

    protected function _addSpecialSubForm(Centurion_Form $form, $name, $order = null, $type = null)
    {
        $this->addSubForm($form, $name, $order);

        if (null !== $type) {
            if (!array_key_exists($type, self::$_specialSubForms)) {
                throw new Centurion_Form_Exception(sprintf("Special form type %s does not exist", $type));
            }

            array_push($this->{self::$_specialSubForms[$type]}, $name);
        }

        return $this;
    }

    protected function _getSpecialSubForm($name, $type)
    {
        if (!array_key_exists($type, self::$_specialSubForms)) {
            throw new Centurion_Form_Exception(sprintf("Special form type %s does not exist", $type));
        }

        if (!in_array($name, $this->{self::$_specialSubForms[$type]})) {
            throw new Centurion_Form_Exception(sprintf("Special form %s for given type %s does not exist", $name, $type));
        }

        return $this->getSubForm($name);
    }

    protected function _getSpecialSubForms($type)
    {
        if (!array_key_exists($type, self::$_specialSubForms)) {
            throw new Centurion_Form_Exception(sprintf("Special form type %s does not exist", $type));
        }

        $subForms = array();

        foreach ($this->getSubForms() as $key => $form) {
            if (!in_array($form->getName(), $this->{self::$_specialSubForms[$type]})) {
                continue;
            }

            array_push($subForms, $form);
        }

        return $subForms;
    }

    /**
     * Process values attached to the form.
     *
     * @param array $values Values
     * @return array Values processed
     */
    protected function _processValues($values)
    {
        $valuesToProcess = $values;

        foreach ($valuesToProcess as $key => $value) {
            $method = sprintf('_update%sColumn', Centurion_Inflector::camelize($key));

            if (method_exists($this, $method)) {
                if (false === ($ret = $this->$method($value))) {
                    unset($values[$key]);
                } else {
                    $values[$key] = $ret;
                }
            }
        }

        return $values;
    }

    protected function _populateManyDependentTables($instance)
    {
        $manyDependentTables = $this->getModel()->info(Centurion_Db_Table_Abstract::MANY_DEPENDENT_TABLES);
        foreach ($manyDependentTables as $key => $manyDependentTable) {

            if ($this->isExcluded($key)) {
                continue;
            }

            $objectsRelated = $instance->{$key};
            $valuesSelected = array();
            foreach ($objectsRelated as $object) {
                array_push($valuesSelected, $object->id);
            }

            if ($el = $this->getElement($key)) {
                $el->setValue($valuesSelected);
            } else if ($form = $this->getSubForm($key)) {
                $form->setValue($valuesSelected);
            }
        }

        return $this;
    }

    /**
     * Event when a form is populated with an instance.
     *
     * @return void
     */
    protected function _onPopulateWithInstance()
    {
        Centurion_Signal::factory('on_populate_with_instance')->send($this);
    }

    /**
     * Save relationship.
     *
     * @todo merge into an adapter, for specific ORM and prepare the port to Doctrine
     * @return void
     */
    protected function _saveManyDependentTables()
    {
        $manyDependentTables = $this->getModel()->info(Centurion_Db_Table_Abstract::MANY_DEPENDENT_TABLES);

        foreach ($manyDependentTables as $key => $manyDependentTable) {
            $objectsRelated = $this->getValue($key);

            if ($this->isExcluded($key) || null === $objectsRelated) {
                continue;
            }

            $intersectionTable = Centurion_Db::getSingletonByClassName($manyDependentTable['intersectionTable']);
            $restrincts = array();
            $i = 0;
            foreach ($objectsRelated as $objectRelated) {
                if (!empty($objectRelated)) {
                    list($intersectionRow, $created) = $intersectionTable->getOrCreate(array(
                        $manyDependentTable['columns']['local']    =>  $this->_instance->id,
                        $manyDependentTable['columns']['foreign']  =>  $objectRelated
                    ));

                    if (isset($intersectionRow->order)) {
                        $intersectionRow->order = $i++;
                        $intersectionRow->save();
                    }

                    $restrincts[] = $this->getModel()->getAdapter()->quoteInto(sprintf('%s != ?', $manyDependentTable['columns']['foreign']),
                                                                           $objectRelated);
                }
             }

            $where = $this->getModel()->getAdapter()->quoteInto(sprintf('%s = ?',
                                                                    $manyDependentTable['columns']['local']),
                                                                    $this->_instance->id);
            if (count($restrincts))
                $where .= sprintf(' AND (%s)', implode(' AND ', $restrincts));

            $rowset = $intersectionTable->fetchAll($intersectionTable->select(true)
                                                                     ->where($where));

            foreach ($rowset as $key => $row) {
                $row->delete();
            }
        }

        return $this;
    }

    /**
     * Parse columns to fields.
     *
     * @param   array   $options
     * @todo Add CSRF key, for security.
     * @return  array   Elements.
     */
    protected function _columnToElements()
    {
        $info = $this->getModel()->info();
        $metadata = $info['metadata'];

        $elements = array();

        foreach ($metadata as $columnName => $columnDetails) {

            if ($this->isExcluded($columnName)) {
                continue;
            }

            if (!isset($this->_elementLabels[$columnName])) {
                continue;
            }

            if ($columnDetails['IDENTITY']) {
                $config = array('hidden', array());
            } elseif (substr($columnName, 0, 2) == 'is' || substr($columnName, 0, 6) == 'can_be') {
                $config = array('checkbox', array());
            } elseif (substr($columnName, 0, -3) == 'pwd' || $columnName == 'password') {
                $config = array('password', array());
            } elseif (preg_match('/^enum/i', $columnDetails['DATA_TYPE'])) {
                preg_match_all('/\'(.*?)\'/', $columnDetails['DATA_TYPE'], $matches);

                $options = (true === $columnDetails['NULLABLE']) ? array(null => ''):array();

                foreach ($matches[1] as $match) {
                    $options[$match] = $match;
                }

                $config = array(
                    'select',
                    array(
                        'multioptions' => $options
                    )
                );
            } elseif (false !== ($reference = $this->getModel()->getReferenceByColumnName($columnName))) {
                if ($this->isDisabled($columnName)) {
                    $config = array(
                        'Reference',
                        array(
                            'reference' => $reference
                        )
                    );
                } else {
                    $config = array(
                        'select',
                        array(
                            'multioptions' => $this->_buildOptions(
                                Centurion_Db::getSingletonByClassName($reference),
                                $columnName,
                                true === $columnDetails['NULLABLE']
                            )
                        )
                    );
                }
            } else {
                $datatype = $columnDetails['DATA_TYPE'];
                $config = array(
                    $this->_columnTypes[$datatype],
                    array()
                );

                if (array_key_exists($datatype, $this->_columnValidators)) {
                    $config[1]['validators'] = array(array(
                        'validator' =>  $this->_columnValidators[$datatype],
                    ));
                    if ($this->_columnValidators[$datatype] == 'stringLength') {
                        $config[1]['validators'][0]['options'] = array(0, $columnDetails['LENGTH']);
                    }
                }
                if (array_key_exists($datatype, $this->_columnFilters))
                    $config[1]['filters'] = $this->_columnFilters[$datatype];
            }

            $config[1] = array_merge($config[1], array(
                'label'     =>  $this->_getElementLabel($columnName),
                'required'  =>  isset($columnDetails['NULLABLE']) ? (bool) $columnDetails['NULLABLE'] != true:false
            ));

            if ($this->isDisabled($columnName))
                $config[1] = array_merge($config[1], array('disabled' => 'disabled'));

            $elements[$columnName] = $config;
        }

        $manyDependentTables = $this->getModel()->info(Centurion_Db_Table_Abstract::MANY_DEPENDENT_TABLES);
        foreach ($manyDependentTables as $key => $manyDependentTable) {

            if ($this->isExcluded($key)) {
                continue;
            }

            $options = array(
                    'label'         =>  $this->_getElementLabel($key)
                );

            //TODO: remove this Media_Model_DbTable_File reference from core
            if ($manyDependentTable['refTableClass'] !== 'Media_Model_DbTable_File') {
                $options['multioptions'] = $this->_buildOptions(
                        Centurion_Db::getSingletonByClassName($manyDependentTable['refTableClass']),
                        $key,
                        false,
                        true
                    );

                $options['multioptions'][null] = '';

                $elementName = 'multiselect';
            } else {
                $elementName = new Media_Form_Element_MultiFile($key, array('parentForm' => $this));
            }

            $elements[$key] = array(
                    $elementName,
                    $options
                );
        }

        return $elements;
    }

    protected function _buildOptions($table, $key, $nullable = false)
    {        
        if (isset($this->_select[$key])) {
            if ($this->_select[$key] instanceof Centurion_Db_Table_Select) {
                $rowset = $table->fetchAll($this->_select[$key]);
            } else if (is_array($this->_select[$key])) {
                $rowset = call_user_func_array($this->_select[$key], array($table->fetchAll($table->select(true))));
            }
        } else {
            Centurion_Db_Table_Abstract::setFiltersStatus(true);
            $rowset = $table->getCache()->fetchAll();
            Centurion_Db_Table_Abstract::restoreFiltersStatus();
        }

        $options = (true === $nullable) ? array(null => ''):array();
        foreach ($rowset as $related) {
            $options[$related->id] = (string) $related;
        }
        
        return $options;
    }

    /**
     * Clean values.
     * Set the value at null if empty.
     *
     * @param array $values
     */
    protected function _cleanValues($values = array())
    {
        foreach ($values as $key => &$value) {
            if (!is_numeric($value) && empty($value)) {
                $value = null;
            }

            if ($this->isDisabled($key) || $this->isExcluded($key)) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Retrieve label for an element.
     *
     * @param   string  $columnName
     * @return  string  Label.
     */
    protected function _getElementLabel($columnName)
    {
        return array_key_exists($columnName, $this->_elementLabels)
               ? $this->_elementLabels[$columnName]
               : '';
    }

    /**
     * Override to provide custom pre-form generation logic
     */
    protected function _preGenerate()
    {
        Centurion_Signal::factory('pre_generate')->send($this);
    }

    /**
     * Override to provide custom post-form generation logic
     */
    protected function _postGenerate()
    {
        Centurion_Signal::factory('post_generate')->send($this);
    }

    /**
     * Override to provide custom post-save logic
     */
    protected function _postSave($isNew = false)
    {
    }

    protected function _preSave()
    {
    }
}