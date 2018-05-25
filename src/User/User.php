<?php
declare(strict_types=1);

/*
 * @author: adrian_panicek
 * @copyright: Pixel federation
 * @license: Internal use only
 */

namespace App\User;

use App\Connection\ConnectionPool;
use React\Socket\ConnectionInterface;

/**
 * Class User
 */
final class User {
	
	/**
	 * @var \React\Socket\ConnectionInterface
	 */
	private $connection;
	
	/**
	 * @var string
	 */
	private $nickName;
	
	/**
	 * @var \App\User\ConnectionPool
	 */
	private $pool;
	
	/**
	 * User constructor.
	 *
	 * @param \App\Connection\ConnectionPool    $pool
	 * @param \React\Socket\ConnectionInterface $connection
	 * @param string                            $nickName
	 */
	public function __construct(ConnectionPool $pool, ConnectionInterface $connection, string $nickName)
	{
		$this->connection = $connection;
		$this->nickName = $nickName;
		$this->pool = $pool;
	}
	
	public function init()
	{
		$this->connection->write(sprintf("Hi, %s. You can change your nickname by typing /nick nickname\n", $this->getNickName()));
		$this->pool->sendAll(sprintf("User %s enters the chat\n", $this->getNickName()), $this);
		
		$this->connection->on('data', function ($data) {
			if ($this->matchCommand($data)) {
				return;
			}
			
			$this->pool->sendAll($this->getNickName() . ": " . $data, $this);
		});
		
		// When connection closes detach it from the pool
		$this->connection->on('close', function() {
			$this->pool->removeUser($this);
			$this->pool->sendAll(sprintf("A user %s left the chat\n", $this->getNickName()), $this);
		});
	}
	
	public function logout()
	{
		$this->connection->close();
	}
	
	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	private function matchCommand(string $message)
	{
		$match = [];
		preg_match('/\/([a-zA-Z]*)(?:\s(.*))/', $message, $match);
		
		if (count($match) < 1) {
			return false;
		}
		
		switch(strtolower($match[1])) {
			case 'nick':
				if (!isset($match[2])) {
					return false;
				}
				$oldName = $this->getNickName();
				$newName = trim($match[2]);
				$this->setNickName($newName);
				$this->pool->sendAll(sprintf("User %s changed nick to %s\n", $oldName, $newName), $this);
				break;
			case 'logout':
				$this->logout();
				break;
			default:
				return false;
		}
		
		return true;
	}
	
	/**
	 * @return \React\Socket\ConnectionInterface
	 */
	public function getConnection(): ConnectionInterface
	{
		return $this->connection;
	}
	
	/**
	 * @param string $name
	 */
	public function setNickName(string $name)
	{
		$this->nickName = $name;
	}
	
	/**
	 * @return string
	 */
	public function getNickName(): string
	{
		return $this->nickName;
	}
}
