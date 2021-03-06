<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Permissions;


use WildPHP\Core\Channels\Channel;
use WildPHP\Core\Commands\Command;
use WildPHP\Core\Commands\CommandHandler;
use WildPHP\Core\Commands\CommandHelp;
use WildPHP\Core\Commands\ParameterStrategy;
use WildPHP\Core\Commands\StringParameter;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Core\Users\User;

class PermissionCommands extends BaseModule
{
	/**
	 * PermissionCommands constructor.
	 *
	 * @param ComponentContainer $container
	 */
	public function __construct(ComponentContainer $container)
	{
		$permissionGroupCollection = PermissionGroupCollection::fromContainer($container);

		CommandHandler::fromContainer($container)->registerCommand('allow',
			new Command(
				[$this, 'allowCommand'],
				new ParameterStrategy(2, 2, [
					'group' => new ExistingPermissionGroupParameter($permissionGroupCollection),
					'permission' => new StringParameter()
				]),
				new CommandHelp([
					'Add a permission to a permission group. Usage: allow [group name] [permission]'
				]),
				'allow'
			));

		CommandHandler::fromContainer($container)->registerCommand('deny',
			new Command(
				[$this, 'denyCommand'],
				new ParameterStrategy(2, 2, [
					'group' => new ExistingPermissionGroupParameter($permissionGroupCollection),
					'permission' => new StringParameter()
				]),
				new CommandHelp([
					'Remove a permission from a permission group. Usage: deny [group name] [permission]'
				]),
				'deny'
			));

		CommandHandler::fromContainer($container)->registerCommand('lsperms',
			new Command(
				[$this, 'lspermsCommand'],
				new ParameterStrategy(1, 1, [
					'group' => new ExistingPermissionGroupParameter($permissionGroupCollection)
				]),
				new CommandHelp([
					'List all members in a permission group. Usage: lsperms [group name]'
				]),
				'lsperms'
			),
			['lsp']);

		$this->setContainer($container);
	}
	
	/**
	 * @param Channel $source
	 * @param User $user
	 * @param $args
	 * @param ComponentContainer $container
	 */
	public function allowCommand(Channel $source, User $user, $args, ComponentContainer $container)
	{
		/** @var PermissionGroup $group */
		$group = $args['group'];
		$permission = $args['permission'];

		$checks = [
			'This group is already allowed to do that.' => $group ? $group->getAllowedPermissions()->contains($permission) : false
		];

		if (!$this->doChecks($checks, $source, $user))
			return;

		$group->getAllowedPermissions()
			->append($permission);
		Queue::fromContainer($container)
			->privmsg($source->getName(), $user->getNickname() . ': This group is now allowed the permission "' . $permission . '"');
	}

	/**
	 * @param Channel $source
	 * @param User $user
	 * @param $args
	 * @param ComponentContainer $container
	 */
	public function denyCommand(Channel $source, User $user, $args, ComponentContainer $container)
	{
		/** @var PermissionGroup $group */
		$group = $args['group'];
		$permission = $args['permission'];

		$checks = [
			'This group is not allowed to do that.' => $group ? !$group->getAllowedPermissions()->contains($permission) : false
		];

		if (!$this->doChecks($checks, $source, $user))
			return;

		$group->getAllowedPermissions()
			->removeAll($permission);
		Queue::fromContainer($container)
			->privmsg($source->getName(), $user->getNickname() . ': This group is now denied the permission "' . $permission . '"');
	}

	/**
	 * @param Channel $source
	 * @param User $user
	 * @param $args
	 * @param ComponentContainer $container
	 */
	public function lspermsCommand(Channel $source, User $user, $args, ComponentContainer $container)
	{
		/** @var PermissionGroup $group */
		$group = $args['group'];

		$perms = $group->getAllowedPermissions()
			->values();
		Queue::fromContainer($container)
			->privmsg($source->getName(),
				$user->getNickname() . ': The following permissions are set for this group: ' . implode(', ', $perms));
	}

	/**
	 * @return string
	 */
	public static function getSupportedVersionConstraint(): string
	{
		return WPHP_VERSION;
	}
}