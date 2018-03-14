-- --------------------------------------------------------
-- 호스트:                          chogoon.com
-- 서버 버전:                        10.1.13-MariaDB - MariaDB Server
-- 서버 OS:                        Linux
-- HeidiSQL 버전:                  9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 테이블 naver_realtime_rank 구조 내보내기
DROP TABLE IF EXISTS `naver_realtime_rank`;
CREATE TABLE IF NOT EXISTS `naver_realtime_rank` (
  `nrr_idx` int(11) NOT NULL AUTO_INCREMENT,
  `nrr_key` varchar(40) DEFAULT NULL,
  `nrr_rank` int(11) DEFAULT NULL,
  `nrr_keyword` varchar(50) DEFAULT NULL,
  `nrr_ndate` datetime DEFAULT NULL,
  `nrr_cdate` datetime DEFAULT NULL,
  PRIMARY KEY (`nrr_idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 내보낼 데이터가 선택되어 있지 않습니다.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
