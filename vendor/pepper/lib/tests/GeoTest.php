<?php

class GeoTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider geoProvider
	 * @param $lat
	 * @param $lon
	 * @param $specials
	 * @param $result
	 */
	function testGeo($lat, $lon, $specials, $result) {
		if ($specials === null) {
			$this->assertTrue($result == call_user_func_array(['\Pepper\Lib\Geo', 'isValid'], [$lat, $lon, [['lat' => 0, 'lon' => 0], ['lat' => -180, 'lon' => -180]]]));
		} else {
			$this->assertTrue($result == call_user_func_array(['\Pepper\Lib\Geo', 'isValid'], [$lat, $lon, $specials]), json_encode([$lat, $lon, $specials, $result]));
		}
	}

	function geoProvider() {
		// lat, lon, specials, expect
		// specials = null表示使用函数默认值
		return [
			[12, 12, [], true],
			[-90, -180, [], true],
			[-90, 180, [], true],
			[0, 0, [], true],
			[0, 0, ['lat' => 0, 'lon' => 0], false],
			[-180, -180, [], false],
			[180, 180, [], false],
			[-90.0, -180.0, [], true],
			[-90.1, -180, [], false],
			[-90, -180.0, [], true],
			[90, 180, [], true],
			[180, 90, [], false],
			[0, 0, null, false],
			[-180, -180, null, false],
		];
	}


	/**
	 * @param $province
	 * @param $standard
	 * @dataProvider standardProvinceProvider
	 */
	function testStandardProvince($province, $standard) {
		$actual = \Pepper\Lib\Geo::getStandardProvince($province);
    	$this->assertEquals($actual, $standard, "province $province, actual is $actual");
	}

	function standardProvinceProvider(){
		return [
			['上海', '上海'],['上海市', '上海'],
			['云南', '云南'],['云南省', '云南'],
			['内蒙古', '内蒙古'],['内蒙古自治区', '内蒙古'],['内蒙古自治区', '内蒙古'],
			['北京', '北京'],['北京市', '北京'],
			['台湾', '台湾'], ['台湾省', '台湾'],
			['台中', '台湾'], ['台中市', '台湾'],
			['台南', '台湾'], ['台南市', '台湾'],
			['台北', '台湾'], ['台北市', '台湾'],
			['新北', '台湾'], ['新北市', '台湾'],
			['桃园', '台湾'], ['桃园市', '台湾'],
			['高雄', '台湾'], ['高雄市', '台湾'],
			['基隆', '台湾'], ['基隆市', '台湾'],
			['新竹', '台湾'], ['新竹市', '台湾'],
			['嘉义', '台湾'], ['嘉义市', '台湾'],
			['桃園', '台湾'], ['桃園市', '台湾'],
			['嘉義', '台湾'], ['嘉義市', '台湾'],
			['吉林', '吉林'],['吉林省', '吉林'],
			['四川', '四川'],['四川省', '四川'],
			['天津', '天津'],['天津市', '天津'],
			['宁夏', '宁夏'],['宁夏省', '宁夏'],['宁夏回族自治区', '宁夏'],
			['安徽', '安徽'],['安徽省', '安徽'],
			['山东', '山东'],['山东省', '山东'],
			['山西', '山西'],['山西省', '山西'],
			['广东', '广东'],['广东省', '广东'],
			['广西', '广西'],['广西省', '广西'],['广西壮族自治区', '广西'],
			['新疆', '新疆'],['新疆省', '新疆'],['新疆维吾尔自治区', '新疆'],
			['江苏', '江苏'],['江苏省', '江苏'],
			['江西', '江西'],['江西省', '江西'],
			['河北', '河北'],['河北省', '河北'],
			['河南', '河南'],['河南省', '河南'],
			['浙江', '浙江'],['浙江省', '浙江'],
			['海南', '海南'],['海南省', '海南'],
			['湖北', '湖北'],['湖北省', '湖北'],
			['湖南', '湖南'],['湖南省', '湖南'],
			['澳门', '澳门'],['澳门特别行政区', '澳门'],
			['甘肃', '甘肃'],['甘肃省', '甘肃'],
			['福建', '福建'],['福建省', '福建'],
			['西藏', '西藏'],['西藏省', '西藏'],['西藏自治区', '西藏'],
			['贵州', '贵州'],['贵州省', '贵州'],
			['辽宁', '辽宁'],['辽宁省', '辽宁'],
			['重庆', '重庆'],['重庆市', '重庆'],
			['陕西', '陕西'],['陕西省', '陕西'],
			['青海', '青海'],['青海省', '青海'],
			['香港', '香港'],['香港特别行政区', '香港'],
			['黑龙江', '黑龙江'],['黑龙江省', '黑龙江'],
			['其他', false],
			['美国', false],
			['火星', false]
		];
	}
}