<?php
declare(strict_types=1);

/*
 * @author: adrian_panicek
 * @copyright: Pixel federation
 * @license: Internal use only
 */
namespace App\Connection;

use App\User\User;
use React\Socket\ConnectionInterface;

final class ConnectionPool {
	
	/** @var \App\User\User[]  */
	protected $users = [];
	
	public function add(ConnectionInterface $connection)
	{
		$user = new User($this, $connection, sprintf("%05d", mt_rand(0, 99999)));
		$user->init();
		$this->users[] = $user;
	}
	
	/**
	 * @param \App\User\User $user
	 */
	public function removeUser(User $user)
	{
		unset($this->users[array_search($user, $this->users)]);
	}
	
	/**
	 * Send data to all connections from the pool except
	 * the specified one.
	 *
	 * @param mixed $data
	 * @param User $except
	 */
	public function sendAll($data, User $except)
	{
		print_r($data);
		array_walk($this->users, function (User $user) use ($data, $except) {
			if ($user === $except) {
				return;
			}
			
			$user->getConnection()->write($data);
		});
	}
}