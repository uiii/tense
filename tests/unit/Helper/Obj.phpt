<?php

require_once __DIR__ . "/../bootstrap.php";

require_once SRC_DIR . "/Helper/Obj.php";

use Tester\Assert;

use Tense\Helper\Obj;

class ObjTest extends Tester\TestCase {
	public function testConfig() {
		$a = new \stdClass();
		$a->a = 5;
		$a->c = [1, 2, 3];
		$a->d = new \stdClass();
		$a->d->a = 8;

		$b = new \stdClass();
		$b->a = 10;
		$b->b = 20;
		$b->c = [4, 5, 6];
		$b->d = new \stdClass();
		$b->d->a = 10;
		$b->d->b = 30;

		$c = new \stdClass();
		$c->d = "d";

		$d = new \stdClass();
		$d->d = new \stdClass();
		$d->d->c = 50;
		$d->e = "e";

		$merged = Obj::merge($a, $b, $c, $d);

		Assert::equal($merged->a, 5);
		Assert::equal($merged->b, 20);
		Assert::equal($merged->c, [1, 2, 3]);
		Assert::equal($merged->d->a, 8);
		Assert::equal($merged->d->b, 30);
		Assert::false(property_exists($merged->d, 'c'));
		Assert::equal($merged->e, "e");
	}
}

$testCase = new ObjTest;
$testCase->run();