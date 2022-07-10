<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class MoveTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @var MovingCube
	 */
	protected Selection $current;

	/**
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return MoveTask
	 */
	public static function from(string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): MoveTask
	{
		$instance = new self($world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param Session    $session
	 * @param MovingCube $selection
	 * @param Position   $place
	 */
	public static function queue(Session $session, MovingCube $selection, Position $place): void
	{
		EditHandler::runPlayerTask($session, self::from($selection->getWorldName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), Vector3::zero()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$direction = $selection->getDirection();
		$handler->getChanges()->checkCachedData();
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $direction): void {
			$handler->changeBlock($x, $y, $z, 0);
			$handler->copyBlock($x + $direction->getFloorX(), $y + $direction->getFloorY(), $z + $direction->getFloorZ(), $x, $y, $z, false);
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}