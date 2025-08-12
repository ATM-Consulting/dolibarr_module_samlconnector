<?php

class SamlConnectorTools
{
	/**
	 * Handles the initial setup for a user upon their first login.
	 *
	 * This method orchestrates the assignment of a default entity and a default group.
	 * It manages its own database transaction to ensure data integrity.
	 *
	 * @param   User    $userObject The user object to configure.
	 * @return  bool                True on complete success, false on failure.
	 */
	public static function configureNewUser(User $userObject) :bool
	{
		global $langs;
		// 1. Get and assign the default entity
		$targetEntityId = getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_ENTITY');
		if (empty($targetEntityId)) {
			dol_syslog("SamlConnectorTools::configureNewUser - Default entity (SAMLCONNECTOR_USER_DEFAULT_ENTITY) is not configured.", LOG_WARNING);
			setEventMessage($langs->trans("SamlConnectorErrorDefaultEntityNotSet"), 'error');
			return false;
		}
		// 2. Get and assign the default group (optional)
		$targetGroupId = getDolGlobalString('SAMLCONNECTOR_USER_DEFAULT_GROUP');
		if (!empty($targetGroupId)) {
			if (!self::assignGroupToUser($userObject, $targetGroupId, $targetEntityId)) {
				return false;
			}
		}
		// 3. Finalize
		dol_syslog("SamlConnectorTools::configureNewUser - Post-configuration successfully completed for '{$userObject->login}'.", LOG_INFO);
		return true;
	}
	/**
	 * Assigns a specific group to a user.
	 *
	 * @param   User    $userToUpdate     The user object to update.
	 * @param   int     $targetGroupId    The ID of the target group.
	 * @param   int     $entityContextId  The ID of the context entity for the assignment.
	 * @return  bool                      true on success, false on failure.
	 */
	public static function assignGroupToUser(User &$userToUpdate, $targetGroupId, $entityContextId) :bool
	{
		if ($userToUpdate->SetInGroup($targetGroupId, $entityContextId) > 0) {
			dol_syslog("samlConnectorTools::assignGroupToUser - Group ID {$targetGroupId} successfully assigned to user '{$userToUpdate->login}'.", LOG_INFO);
			return true;
		} else {
			dol_syslog("samlConnectorTools::assignGroupToUser - Failed to assign group ID {$targetGroupId} to '{$userToUpdate->login}'. Error: " . $userToUpdate->error, LOG_ERR);
			return false;
		}
	}
}
