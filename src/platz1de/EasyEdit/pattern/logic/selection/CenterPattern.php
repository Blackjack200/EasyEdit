<?php

namespace platz1de\EasyEdit\pattern\logic\selection;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TaskCache;

class CenterPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		if ($selection instanceof Cube) {
			$min = TaskCache::getFullSelection()->getPos1();
			$max = TaskCache::getFullSelection()->getPos2();

			$xPos = ($min->getX() + $max->getX()) / 2;
			$yPos = ($min->getY() + $max->getY()) / 2;
			$zPos = ($min->getZ() + $max->getZ()) / 2;

			return floor($xPos) <= $x && $x <= ceil($xPos) && floor($yPos) <= $y && $y <= ceil($yPos) && floor($zPos) <= $z && $z <= ceil($zPos);
		}
		if ($selection instanceof Cylinder || $selection instanceof Sphere) {
			return $x === $selection->getPoint()->getFloorX() && $y === $selection->getPoint()->getFloorY() && $z === $selection->getPoint()->getFloorZ();
		}
		throw new ParseError("Center pattern does not support selection of type " . $selection::class);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeCenter();
	}
}