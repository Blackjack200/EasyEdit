<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

class InjectingData
{
	private array $blockData = [];
	private BlockPosition $position;

	public function __construct(int $x, int $y, int $z)
	{
		$this->position = new BlockPosition($x, $y, $z);
	}

	public function writeBlock(int $x, int $y, int $z, int $id): void
	{
		$this->blockData[] = [$x, $y, $z, $id];
	}

	public function toProtocol(int $protocol) : string {
		$serializer = PacketSerializer::encoder($protocol);
		$serializer->putBlockPosition($this->position);
		$serializer->putUnsignedVarInt(count($this->blockData));
		foreach ($this->blockData as [$x, $y, $z, $id]) {
			$serializer->putVarInt($x);
			$serializer->putUnsignedVarInt(Binary::unsignInt($y));
			$serializer->putVarInt($z);
			$serializer->putUnsignedVarInt(TypeConverter::getInstance($protocol)->getBlockTranslator()->internalIdToNetworkId($id));
			$serializer->putUnsignedVarInt(2); //network flag
			$serializer->putUnsignedVarLong(-1); //we don't have any actors
			$serializer->putUnsignedVarInt(0); //not synced
		}
		$serializer->putUnsignedVarInt(0); //we don't use the second layer
		return $serializer->getBuffer();
	}
}