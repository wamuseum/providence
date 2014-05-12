<?php
/** ---------------------------------------------------------------------
 * support/tests/plugins/RelationshipGeneratorPluginConfigurationTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once 'PHPUnit/Autoload.php';
require_once __CA_APP_DIR__ . '/plugins/relationshipGenerator/relationshipGeneratorPlugin.php';

/**
 * Tests the configuration-checking functionality of the plugin.  Simply constructs the plugin with different (both
 * valid and invalid) configuration files, and asserts against the result of calling checkStatus().
 */
class RelationshipGeneratorConfigurationPluginTest extends PHPUnit_Framework_TestCase {

	public function testDefaultConfigurationIsEnabledAndValid() {
		$vo_plugin = new relationshipGeneratorPlugin(__CA_APP_DIR__ . '/plugins/relationshipGenerator');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertTrue($va_pluginStatus['available'], 'The plugin is enabled by default');
		$this->assertEmpty($va_pluginStatus['errors'], 'The default configuration does not produce any errors');
	}

	public function testDisabledConfigurationIsDisabled() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/disabled-plugin');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertFalse($va_pluginStatus['available'], 'The plugin can be disabled by configuration');
		$this->assertEmpty($va_pluginStatus['errors'], 'Disabling the plugin via configuration does not produce any errors');
	}

	public function testEmptyConfigurationFileGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/empty-configuration');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertCorrectErrorMessages(
			array(
				array( '`default_field_combination_operator`', _t('top level') ),
				array( '`default_value_combination_operator`', _t('top level') ),
				array( '`default_match_type`', _t('top level') ),
				array( '`default_match_options`', _t('top level') ),
				array( '`rules`', _t('top level') )
			),
			$va_pluginStatus['errors'],
			'An empty configuration produces the correct number of errors',
			'An empty configuration produces error message containing "%1"'
		);
	}

	public function testInvalidOperatorsInConfigurationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/invalid-operators');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertCorrectErrorMessages(
			array(
				array( 'default_field_combination_operator', _t('top level'), '"INVALID"' ),
				array( 'value_combination_operator', _t('rule %1', 0), '"INVALID"' ),
				array( 'value_combination_operator', _t('trigger field %1 on rule %2', 'testField', 0), '"INVALID"' )
			),
			$va_pluginStatus['errors'],
			'A configuration specifying incorrect operators produces the correct number of error messages',
			'A configuration specifying incorrect operators produces an error message containing "%1"'
		);
	}

	public function testInvalidMatchTypesInConfigurationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/invalid-match-types');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertCorrectErrorMessages(
			array(
				array( 'default_match_type', _t('top level'), '"INVALID"' ),
				array( 'match_type', _t('rule %1', 0), '"INVALID"' ),
				array( 'match_type', _t('trigger field %1 on rule %2', 'testField', 0), '"INVALID"' )
			),
			$va_pluginStatus['errors'],
			'A configuration specifying incorrect match types produces the correct number of error messages',
			'A configuration specifying incorrect match types produces an error message containing "%1"'
		);
	}

	public function testEmptyRuleSpecificationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/empty-rule-spec');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertCorrectErrorMessages(
			array(
				array( 'source_tables', _t('rule %1', 0) ),
				array( 'triggers', _t('rule %1', 0) ),
				array( 'related_table', _t('rule %1', 0) ),
				array( 'related_record', _t('rule %1', 0) ),
				array( 'relationship_type', _t('rule %1', 0) )
			),
			$va_pluginStatus['errors'],
			'A configuration specifying incorrect match types produces the correct number of error messages',
			'A configuration specifying incorrect match types produces an error message containing "%1"'
		);
	}

	public function testInvalidRuleSpecificationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/invalid-rule-spec');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertCorrectErrorMessages(
			array(
				array( 'source_tables', _t('rule %1', 0) ),
				array( 'triggers', _t('rule %1', 0) ),
				array( 'related_table', _t('rule %1', 0) ),
				array( 'relationship_type', _t('rule %1', 0) ),
				array( 'source_tables', _t('rule %1', 1) ),
				array( 'triggers', _t('rule %1', 1) ),
				array( 'related_table', _t('rule %1', 1) ),
				array( 'relationship_type', _t('rule %1', 1) )
			),
			$va_pluginStatus['errors'],
			'A configuration specifying incorrect match types produces the correct number of error messages',
			'A configuration specifying incorrect match types produces an error message containing "%1"'
		);
	}

	protected function assertCorrectErrorMessages($pa_expectedErrorMessageContents, $pa_actualErrorMessages, $ps_sizeCheckDescription, $ps_valueCheckDescriptionTemplate) {
		$this->assertEquals(sizeof($pa_expectedErrorMessageContents), sizeof($pa_actualErrorMessages), _t($ps_sizeCheckDescription));
		foreach ($pa_expectedErrorMessageContents as $va_expectedContent) {
			foreach ($va_expectedContent as $vs_expectedContentSubstring) {
				$this->assertRegExp('/' . str_replace('/', '\\/', $vs_expectedContentSubstring) . '/', _t($ps_valueCheckDescriptionTemplate, $vs_expectedContentSubstring));
			}
		}
	}
}