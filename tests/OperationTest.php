<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesAutomatedTagging\Tests;

use OCA\FilesAutomatedTagging\Operation;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class OperationTest extends TestCase {

	/** @var \OCP\SystemTag\ISystemTagObjectMapper|\PHPUnit_Framework_MockObject_MockObject */
	protected $objectMapper;
	/** @var \OCP\WorkflowEngine\IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $checkManager;
	/** @var \OCA\FilesAutomatedTagging\Operation */
	protected $operation;

	protected function setUp() {
		parent::setUp();

		$this->objectMapper = $this->getMockBuilder('OCP\SystemTag\ISystemTagObjectMapper')
			->getMock();
		$this->checkManager = $this->getMockBuilder('OCP\WorkflowEngine\IManager')
			->getMock();
		$this->operation = new Operation(
			$this->objectMapper, $this->checkManager
		);
	}

	protected function getStorageMock() {
		return $this->getMockBuilder('OCP\Files\Storage\IStorage')
			->getMock();
	}

	public function dataCheckOperations() {
		return [
			[$this->getStorageMock(), 123, 'path', [], []],
			[$this->getStorageMock(), 42, 'path2', [['operation' => '2']], [
				[2],
			]],
			[$this->getStorageMock(), 23, 'path2', [
				['operation' => '2,3'],
				['operation' => '42']
			],[
				[2, 3],
				[42],
			]],
		];
	}

	/**
	 * @dataProvider dataCheckOperations
	 *
	 * @param IStorage $storage
	 * @param int $fileId
	 * @param string $file
	 * @param array[] $matches
	 * @param array[] $expected
	 */
	public function testCheckOperations(IStorage $storage, $fileId, $file, array $matches, array $expected) {

		$this->checkManager->expects($this->once())
			->method('setFileInfo')
			->with($storage, $file);

		$this->checkManager->expects($this->once())
			->method('getMatchingOperations')
			->with('OCA\FilesAutomatedTagging\Operation', false)
			->willReturn($matches);

		foreach ($expected as $key => $tags) {
			$this->objectMapper->expects($this->at($key))
				->method('assignTags')
				->with($fileId, 'files', $tags);
		}

		$this->operation->checkOperations($storage, $fileId, $file);
	}
}
