<?php
/* ----------------------------------------------------------------------
 * wamDuplicationPlugin.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
 * This file originally contributed 2014 by Gaia Resources
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

/**
 * The duplication plugin performs duplication related tasks
 */
class wamDuplicationPlugin extends BaseApplicationPlugin
{
	const DEFAULT_KEY = '__default__';

	/** @var Configuration */
	private $opo_config;

	/** @var NotificationManager */
	private $opo_notifications;

	private $ovo_status = null;

	private static $s_message_types = array(
		'info' => __NOTIFICATION_TYPE_INFO__,
		'warning' => __NOTIFICATION_TYPE_WARNING__,
		'error' => __NOTIFICATION_TYPE_ERROR__
	);

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('wam Duplication Plugin');
		$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'wamDuplication.conf');
	}

	public function checkStatus() {
		if(isset($this->ovo_status)){
			return $this->ovo_status;
		}
		$va_errors = array();
		$va_warnings = array();
		$va_replacements = $this->opo_config->getAssoc('duplicate_replacements');
		if (!is_array($va_replacements)) {
			$va_errors[] = _t('"duplicate_replacements" should be a nested array in the <a href="http://docs.collectiveaccess.org/wiki/Configuration_File_Syntax">CA Configuration File Syntax</a>');
		}
		$o_dm = Datamodel::load();
		$va_tables = $o_dm->getTableNames();
		foreach ($va_replacements as $vs_table => $va_type_list) {
			if (array_search($vs_table, $va_tables) !== false) {
				if (is_array($va_type_list)) {
					foreach ($va_type_list as $vs_type => $va_type_mapping) {
						if (is_array($va_type_mapping)) {
							foreach ($va_type_mapping as $vs_field => $va_rules) {
								$this->_validateMapping($vs_field, $va_rules, $vs_type, $vs_table, $va_errors);
							}
						} else {
							$va_errors[] = _t('Table "%1" type "%2" should be an array of fields', $vs_table, $vs_type);
						}
					}
				} else {
					$va_errors[] = _t('Table "%1" type list should be an array.', $vs_table);
				}
			} else {
				$va_errors[] = _t('Table "%1" does not exist.', $vs_table);
			}
		}
		$vs_local_config_path = __CA_LOCAL_CONFIG_DIRECTORY__ . DIRECTORY_SEPARATOR . basename($this->opo_config->ops_config_file_path);
		$vs_config_path = file_exists($vs_local_config_path) ? $vs_local_config_path : $this->opo_config->ops_config_file_path;

		if ($va_errors) {
			$va_errors[] = _t(
				'%1: %2 error(s). Check your configuration file at %3 to fix these errors.',
				$this->getDescription(),
				sizeof($va_errors),
				$vs_config_path
			);
		}
		if ($va_warnings) {
			$va_warnings[] = _t(
				'%1: %2 warning(s). Check your configuration file at %3 to fix these warnings.',
				$this->getDescription(),
				sizeof($va_warnings),
				$vs_config_path
			);
		}
		$this->ovo_status = array(
			'description' => $this->getDescription(),
			'errors' => $va_errors,
			'warnings' => $va_warnings,
			'available' => ((bool)$this->opo_config->getBoolean('enabled'))
		);
		return $this->ovo_status;
	}

	static function getRoleActionList() {
		return array();
	}

	/**
	 * @param $pa_params array with the following keys. Note this array is passed by reference:
	 * 'id' => null since no id is set before the insert.
	 * 'table_num' => $t_subject->tableNum() - the table number of the BundleableLabelableBaseModelWithAttributes being inserted
	 * 'table_name' => $t_subject->tableName() - the base table of the BundleableLabelableBaseModelWithAttributes being inserted
	 * 'instance' => $t_subject - the BundleableLabelableBaseModelWithAttributes being duplicated
	 * @return array $params for other plugins to act on it
	 */
	public function hookBeforeBundleInsert(&$pa_params) {
		if ($this->getRequest()->getParameter('mode', pString) === 'dupe') {
			$this->_runDuplicationMappings($pa_params['instance'], __FUNCTION__);
		}
		return $pa_params;
	}

	/**
	 * @param $pa_params array with the following keys. Note this array is passed by reference:
	 * 'id' => $vn_subject_id - the id of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'table_num' => $t_subject->tableNum() - the table number of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'table_name' => $t_subject->tableName() - the base table of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'instance' => $t_subject - the BundleableLabelableBaseModelWithAttributes being duplicated
	 * @return array $params for other plugins to act on it
	 */
	public function hookBeforeDuplicateItem(&$pa_params) {
		$this->_runDuplicationMappings($t_subject = $pa_params['instance'], __FUNCTION__);
		return $pa_params;
	}

	/**
	 * @param $pa_params array with the following keys. Note this array is passed by reference:
	 * 'id' => $vn_subject_id - the id of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'table_num' => $t_subject->tableNum() - the table number of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'table_name' => $t_subject->tableName() - the base table of the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'instance' => $t_subject - the BundleableLabelableBaseModelWithAttributes being duplicated
	 * 'duplicate' => $t_dupe - the new BundleableLabelableBaseModelWithAttributes
	 * @return array $params for other plugins to act on it
	 */
	public function hookDuplicateItem(&$pa_params) {
		$this->_runDuplicationMappings($t_subject = $pa_params['instance'], __FUNCTION__);
		return $pa_params;
	}

	private function _addNotification($ps_message, $pn_level = __NOTIFICATION_TYPE_INFO__) {
		if (!$this->opo_notifications) {
			$this->opo_notifications = new NotificationManager($this->getRequest());
		}
		$this->opo_notifications->addNotification($this->getDescription() . ': ' . $ps_message, $pn_level);
	}

	/**
	 * @param $po_instance BundlableLabelableBaseModelWithAttributes being duplicated
	 * @param $ps_hook string hook calling this method
	 */
	private function _runDuplicationMappings($po_instance, $ps_hook) {
		if($this->ovo_status['errors'] || $this->ovo_status['warnings']){
			// We can't use 'available' as we then never see any configuration errors during check status
			return;
		}
		$ps_table_name = $po_instance->tableName();
		$vs_instance_type = $po_instance->getTypeCode();
		$va_replacements = $this->opo_config->getAssoc('duplicate_replacements');
		if (isset($va_replacements[$ps_table_name])) {
			$va_type_mappings = $va_replacements[$ps_table_name];
			// Does this record match the type or is there a __default__
			$vs_type = null;
			if (isset($va_type_mappings[$vs_instance_type])) {
				$vs_type = $vs_instance_type;
			} elseif (isset($va_type_mappings[self::DEFAULT_KEY])) {
				$vs_type = self::DEFAULT_KEY;
			}
			if ($vs_type) {
				foreach ($va_type_mappings[$vs_type] as $vs_field => $va_rules) {
					$vs_pattern = $va_rules['pattern'];
					caDebug($vs_pattern, 'pattern');
					$vs_replace = $va_rules['replace'];
					caDebug($vs_replace, $va_rules['replace']);
					$vs_hook = $va_rules['hook'];
					if ($vs_hook !== $ps_hook) {
						// Only perform actions if we were called by this hook
						break;
					}
					$vs_original = $po_instance->get($vs_field);
					if ($vs_original) {
						caDebug($vs_original, 'original');
						$vs_new = preg_replace($vs_pattern, $vs_replace, $vs_original);
						caDebug(array('pattern' => $vs_pattern, 'replace' => $vs_replace, 'original' => $vs_original, 'new' => $vs_new), 'all', true);
						if ($vs_new != $vs_original) {
							$po_instance->set($vs_field, $vs_new);
						}
						if (isset($va_rules['message'])) {
							$vn_message_type = self::$s_message_types[isset($va_rules['message_type']) ? $va_rules['message_type'] : 'info'];
							$this->_addNotification(_t($va_rules['message'], $vs_original, $vs_new), $vn_message_type);
						}
					}
				}
			}
		}
	}

	/**
	 * @param $vs_field string name of the mapping that the field is for
	 * @param $va_rules array mapping rules
	 * @param $vs_type string record type
	 * @param $ps_table_name string table name
	 * @param $pa_errors array reference to the errors array to allow any errors to be added to it
	 * @return bool as to whether the mapping is valid
	 */
	private function _validateMapping($vs_field, $va_rules, $vs_type, $ps_table_name, &$pa_errors) {
		$vb_valid = true;
		foreach (array('pattern', 'replace', 'hook') as $vs_required_field) {
			if (!isset($va_rules[$vs_required_field])) {
				$pa_errors[] = _t('Mapping for field %1 of type %2 on table %3 requires a %4 key', $vs_field, $vs_type, $ps_table_name, $vs_required_field);
				$vb_valid = false;
			} elseif ($vs_required_field === 'hook') {
				if (!method_exists($this, $va_rules[$vs_required_field])) {
					$pa_errors [] = _t('Hook %1 does not exist in mapping %2 of type %3 for field %4', $va_rules[$vs_required_field], $ps_table_name, $vs_type, $vs_field);
					$vb_valid = false;
				}
			}
		}
		return $vb_valid;
	}
}
