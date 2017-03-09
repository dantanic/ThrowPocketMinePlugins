# TreadFarmland

## Add Event
- [TreadFarmlandEvent](https://github.com/organization/TreadFarmland/blob/master/Source/src/TreadFarmland/event/TreadFarmlandEvent.php) extends BlockPlaceEvent
````php
	public function onBlockPlace(BlockPlaceEvent $event){
		if($event instanceof TreadFarmlandEvent){
			echo "Handle TreadFarmlandEvent";
		}
	}
````

## Replace Player class
- [TreadFarmLandPlayer](https://github.com/organization/TreadFarmland/blob/master/Source/src/TreadFarmland/player/TreadFarmlandPlayer.php) extends Player

## Reference
- PMMP is not break the crops when floor block is not farmland
- Just check 'isTransparent()'
- Therefore, If you want to work properly, should apply the [CropPlus](https://github.com/organization/CropPlus) on PMMP.



```
```

```
```

```
```


# in Korean 한국어

## 이벤트 추가
- [TreadFarmlandEvent](https://github.com/organization/TreadFarmland/blob/master/Source/src/TreadFarmland/event/TreadFarmlandEvent.php) extends BlockPlaceEvent
````php
	public function onBlockPlace(BlockPlaceEvent $event){
		if($event instanceof TreadFarmlandEvent){
			echo "Handle TreadFarmlandEvent";
		}
	}
````

## Player클래스를 치환
- [TreadFarmLandPlayer](https://github.com/organization/TreadFarmland/blob/master/Source/src/TreadFarmland/player/TreadFarmlandPlayer.php) extends Player

## 참조
- PMMP가 바닥이 경작지가 아닐때 작물을 부수지않습니다.
- 단지 'isTransparent()'만 체크합니다.
- 그러므로, 당신이 이것이 제대로 작동하기 원한다면, 반드시 [CropPlus](https://github.com/organization/CropPlus)를 PMMP에 적용해야합니다.