<?php

namespace Pepper\Lib;

class Geo
{

    /**
     * 判断给定的经纬度是否合法
     * @param double $lat
     * @param double $lon
     * @param array $specials 不合法的特殊经纬度，如果0,0 -180,-180。格式：[['lat' => 0, 'lon' => 0], ['lat' => -180, 'lon' => -180]]
     * @return bool
     */
    public static function isValid($lat, $lon, $specials = [['lat' => 0, 'lon' => 0], ['lat' => -180, 'lon' => -180]])
    {
        foreach ($specials as $geo) {
            if ($lat == $geo['lat'] && $lon == $geo['lon']) {
                return false;
            }
        }
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

	/**
	 * 标准化省份名称
	 * @param string $province
	 * @return bool|string 如果存在标准省份中，则返回标准省份名称，否则返回false
	 */
	public static function getStandardProvince($province){
		$map = [
			'上海' => ['上海'],
			'云南' => ['云南'],
			'内蒙古' => ['内蒙古'],
			'北京' => ['北京'],
			'台湾' => ['台湾','台中','台南','台北','新北','桃园','高雄','基隆','新竹','嘉义','桃園','嘉義'],
			'吉林' => ['吉林'],
			'四川' => ['四川'],
			'天津' => ['天津'],
			'宁夏' => ['宁夏'],
			'安徽' => ['安徽'],
			'山东' => ['山东'],
			'山西' => ['山西'],
			'广东' => ['广东'],
			'广西' => ['广西'],
			'新疆' => ['新疆'],
			'江苏' => ['江苏'],
			'江西' => ['江西'],
			'河北' => ['河北'],
			'河南' => ['河南'],
			'浙江' => ['浙江'],
			'海南' => ['海南'],
			'湖北' => ['湖北'],
			'湖南' => ['湖南'],
			'澳门' => ['澳门'],
			'甘肃' => ['甘肃'],
			'福建' => ['福建'],
			'西藏' => ['西藏'],
			'贵州' => ['贵州'],
			'辽宁' => ['辽宁'],
			'重庆' => ['重庆'],
			'陕西' => ['陕西'],
			'青海' => ['青海'],
			'香港' => ['香港'],
			'黑龙江' => ['黑龙江'],
		];
		foreach ($map as $standardProvince => $keywords){
			foreach ($keywords as $keyword){
				if (strpos($province, $keyword) !== false){
					return $standardProvince;
				}
			}
		}
		return false;
	}
}