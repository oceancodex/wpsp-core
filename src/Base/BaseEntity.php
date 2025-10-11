<?php
namespace WPSPCORE\Base;

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

	public function setCreatedAt($createdAt) {
		$this->createdAt = $createdAt;
	}

	public function getCreatedAt() {
		return $this->createdAt;
	}

	public function setUpdatedAt($updatedAt) {
		$this->updatedAt = $updatedAt;
	}

	public function getUpdatedAt() {
		return $this->updatedAt;
	}

	public function setDeletedAt($deletedAt) {
		$this->deletedAt = $deletedAt;
	}

	public function getDeletedAt() {
		return $this->deletedAt;
	}

}