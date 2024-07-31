<?php
namespace WPSPCORE\Base;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

abstract class BaseEntity {

	#[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
	protected $createdAt;

	#[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
	protected $updatedAt;

	#[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
	protected $deletedAt;

	/*
	 *
	 */

	public function setCreatedAt(DateTime $createdAt): void {
		$this->createdAt = $createdAt;
	}

	public function getCreatedAt(): DateTime {
		return $this->createdAt;
	}

	public function setUpdatedAt(DateTime $updatedAt): void {
		$this->updatedAt = $updatedAt;
	}

	public function getUpdatedAt(): DateTime {
		return $this->updatedAt;
	}

	public function setDeletedAt($deletedAt): void {
		$this->deletedAt = $deletedAt;
	}

	public function getDeletedAt(): DateTime {
		return $this->deletedAt;
	}

}