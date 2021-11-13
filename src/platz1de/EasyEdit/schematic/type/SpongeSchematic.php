<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\schematic\BlockConvertor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use UnexpectedValueException;

class SpongeSchematic extends SchematicType
{
	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		$version = $nbt->getInt("Version", 1);
		//TODO: Metadata
		//TODO: Offset
		$xSize = $nbt->getShort("Width");
		$ySize = $nbt->getShort("Height");
		$zSize = $nbt->getShort("Length");
		$target->setPoint(new Vector3(0, 0, 0));
		$target->setPos1(new Vector3(0, World::Y_MIN, 0));
		$target->setPos2(new Vector3($xSize, $ySize, $zSize));
		$target->getManager()->load($target->getPos1(), $target->getPos2());

		$blockData = $nbt->getByteArray("BlockData");
		$paletteData = $nbt->getCompoundTag("Palette");
		if ($paletteData === null) {
			throw new UnexpectedValueException("Schematic is missing palette");
		}
		$palette = [];

		foreach ($paletteData->getValue() as $name => $id) {
			$palette[$id->getValue()] = BlockConvertor::getFromState($name);
		}

		$i = 0;
		for ($y = 0; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					$index = ord($blockData[$i]);
					[$id, $meta] = $palette[$index];

					$target->addBlock($x, $y, $z, ($id << Block::INTERNAL_METADATA_BITS) | $meta);
					$i++;
				}
			}
		}

		//TODO: tiles and entities
	}
}