-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 12:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookstore`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reduce_stock` (IN `p_book_id` INT, IN `p_quantity` INT)   BEGIN
    UPDATE books 
    SET stock_quantity = stock_quantity - p_quantity
    WHERE book_id = p_book_id 
    AND stock_quantity >= p_quantity;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_generate_order_number` () RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DETERMINISTIC BEGIN
    DECLARE next_number INT;
    DECLARE order_num VARCHAR(20);
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, 12) AS UNSIGNED)), 0) + 1 
    INTO next_number
    FROM orders 
    WHERE order_number LIKE CONCAT('ORD', DATE_FORMAT(NOW(), '%Y%m%d'), '%');
    
    SET order_num = CONCAT('ORD', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(next_number, 3, '0'));
    
    RETURN order_num;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default-book.jpg',
  `is_active` tinyint(1) DEFAULT 1,
  `is_bestseller` tinyint(1) DEFAULT 0,
  `is_new` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_percent` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category_id`, `price`, `stock_quantity`, `description`, `image`, `is_active`, `is_bestseller`, `is_new`, `created_at`, `updated_at`, `discount_percent`) VALUES
(1, '9786160829446', 'The Whale Store (คุณวาฬร้านชำ)', 'Snow Leopard (เสือดาวหิมะ)', 'สำนักพิมพ์ทำดี', '2023', 1, 285.00, 49, '\"See that closed sign over there? Its eleven p.m., what kind of grocery store opens at eleven!\"\r\n\r\nWan was a skilled secretary who suddenly had to take over her familys grocery store out of the blue. Whats worse, she had to deal with Maewnam, a strange customer who kept coming to her store and messing with her constantly. Lately, she seemed to be messing with her heart as well!\r\n\r\nIt was hard enough to take care of the grocery store. Could she not give Wan any more headaches, please?!', '697634c67facd.gif', 1, 1, 1, '2026-01-25 06:21:19', '2026-02-03 10:05:04', 0),
(2, '9786161848590', 'เขมจิราต้องรอด', 'คาลิ', 'สำนักพิมพ์ใจดี', '2023', 1, 479.00, 4, 'เขมเกิดขึ้นมาในครอบครัวต้องสาป ที่ว่าด้วยการให้กำเนิดบุตร\r\nหากเป็นบุตรสาวจะแคล้วคลาดปลอดภัย\r\nหากเป็นบุตรชาย ผู้นั้นต้องตกตายก่อนอายุยี่สิบปี\r\nมารดาตั้งชื่อเขมว่า ‘เขมจิรา’ ที่เป็นชื่อของหญิงสาวเพื่อแก้เคล็ด\r\n‘เขมจิรา’ แปลว่าปลอดภัยตลอดกาล\r\nเขมเชื่อแบบนั้นจนกระทั่งวันเกิดอายุครบสิบเก้าปี...\r\n\r\n#เขมจิราต้องรอด', '697633a6caafb.gif', 1, 1, 0, '2026-01-25 06:21:19', '2026-02-09 17:28:51', 10),
(3, '9786160829330', 'One Piece เล่ม 105', 'โออิจิโระ โอดะ', 'สยามอินเตอร์คอมิคส์', '2023', 2, 75.00, 98, 'การ์ตูนผจญภัยสุดฮา', '69762eb60c649.jpg', 1, 1, 1, '2026-01-25 06:21:19', '2026-02-03 10:05:04', 0),
(6, '9786160829002', 'จิตวิทยาเพื่อชีวิตที่ดีขึ้น', 'หมอจิต', 'สำนักพิมพ์สุขภาพจิต', '2023', 6, 280.00, 4, 'ทำความเข้าใจตัวเอง', '69762b917bc6f.jpg', 1, 0, 0, '2026-01-25 06:21:19', '2026-01-25 16:29:41', 0),
(7, '9786160828890', 'ภาพวาดปริศนากับการตามหาฆาตกร (พิมพ์ใหม่)', 'อุเก็ตสึ', 'สำนักพิมพ์มหาวิทยาลัย', '2022', 1, 420.00, 0, 'ภาพวาดปริศนากับการตามหาฆาตกร (พิมพ์ใหม่)\r\n\r\nสี่เรื่องราวจากภาพวาดที่ยากจะคาดเดา\r\n• หญิงสาวที่เสียชีวิตจากการคลอดลูก เหลือไว้เพียงปริศนาจากภาพวาดห้าชิ้น ที่ไม่มีใครแก้ปริศนาได้ (ภาพที่ 1)\r\n• เด็กชายที่วาดรูปตนกับแม่ ยืนอยู่ข้างอพาร์ตเมนต์ที่อยู่อาศัย แต่บริเวณห้องกลับมีหมอกดำปกคลุมอยู่ (ภาพที่ 2)\r\n• ครูสอนศิลปะที่เสียชีวิตบนหุบเขา พร้อมกับภาพวาดบนหุบเขาชั้นแปดที่ทุกคนต่างถามว่า วาดทำไม (ภาพที่ 3)\r\n• เด็กหญิงที่วาดรูปบ้านและต้นไม้ ภายในต้นไม้มียกเขาชวาอาศัยอยู่ เพระเธออยากปกป้องคนที่อ่อนแอ (ภาพที่ 4)\r\nทั้งสี่เรื่องคือปริศนาที่ให้คนอ่านได้มาขบคิดถึงความวิปริต วิปลาสในจิตใจคน จากภาพวาดที่คาดไม่ถึงว่าจะมีความลับอะไรซ่อนอยู่ในนั้น', '69762bfb0c030.webp', 1, 0, 0, '2026-01-25 06:21:19', '2026-02-09 15:35:52', 0),
(8, '9786160828784', 'Dare You To Death ไขคดีเป็นเห็นคดีตาย', 'MTRD.S', 'สำนักพิมพ์ทราเวล', '2023', 1, 419.00, 41, 'เมื่อ \'คามิน\' สารวัตรคนใหม่ของสถานี และผู้กองสุดห่ามอย่าง \'เจษ\' ต้องร่วมกันไขคดีปริศนาของเพื่อนรักทั้งแปดคนที่เกิดขึ้นหลังจากดื่มสังสรรค์และเล่นเกม TRUTH OR DARE\r\nเกมที่ควรสนุก กลับน่าสะพรึงเมื่อมีคนตายหลังจากเล่นเกม จากแปดในช่วงกลางคืน กลับเหลือเพียงเจ็ดในเช้าวันถัดมา\r\nแทนที่เกมจะจบลงในคืนนั้น มันกลับยังดำเนินต่อไป โดยมีชีวิตทุกคนเป็นหมากในเกม\r\nมีปริศนามากมายชวนให้คิดไม่ตก และพวกเขาต้องรีบหาคำตอบ ก่อนที่ทุกอย่างจะสายเกินไป', '6976348206458.gif', 1, 1, 1, '2026-01-25 06:21:19', '2026-01-25 15:19:30', 0),
(9, NULL, 'Love Design รับ(รัก)ออกแบบ', 'THEK34', NULL, NULL, 1, 322.00, 111, 'แผนของ ออกแบบ คือกลับมาช่วยพี่ชายแปลงโฉมบริษัท ควบคู่ไปกับการแก้เกมคู่แข่งซึ่งเคยขโมยผลงานของเธอไปใช้อย่างหน้าไม่อาย\r\nไม่มีอะไรง่ายดาย เพราะนอกจากผู้หญิงคนนั้นจะเป็นรักแรกของเธอแล้ว ก็ยังมีคนที่ไม่เคยอยู่ในแผนการโผล่เข้ามาเป็นตัวแปรสำคัญในภารกิจนี้ด้วย\r\nริน คือสถาปนิกอัจฉริยะที่ใครต่างก็ต้องการตัว แต่ความเก่งกาจในการทำงานนั้นแลกมาด้วยความกวนประสาทและเอาแต่ใจอย่างที่สุด\r\nท่ามกลางเดิมพันเรื่องงาน คนในอดีต และความรู้สึกจากส่วนลึกข้างใน เด็กสาวเข้ามาทำให้ทุกอย่างปั่นป่วน ไม่เว้นแม้แต่หัวใจของเธอที่ถูกออกแบบไว้ไม่ให้สั่นไหวกับใครง่าย ๆ\r\n\"คนเราจะตกหลุมรักคนที่ไม่คิดว่าจะรักมาตั้งแต่แรกได้หรือเปล่านะ? เจ๊คิดว่าไง?\"', '697634ffae5f4.gif', 1, 0, 0, '2026-01-25 15:21:35', '2026-01-25 15:21:35', 0),
(10, NULL, 'THE FIRE #โซ่รักอัคนี', 'แซลม่อน', NULL, NULL, 1, 299.00, 4, 'เมื่อ อัจจิมา วาทินวณิช นายน้อยทายาทเศรษฐีแห่งเมืองใต้\r\nได้กลับมาพบกับ กะเพรา อาทิตยา คู่ปรับเก่าตั้งแต่สมัยเด็ก\r\nผู้ซึ่งเป็นตัวปัญหาและชอบแย่งความรักจากคนเป็นพ่อไป\r\nงานนี้ความสัมพันธ์ที่คนหนึ่งเปรียบเหมือนเปลวไฟรุ่มร้อน\r\nอีกคนก็เปรียบเหมือนน้ำมันที่พร้อมจะทำให้ไฟยิ่งลุกโชน\r\nจะแตกร้าวซ้ำรอยวันวาน หรือประกอบขึ้นใหม่เป็นรักที่มั่นคง', '69763541d1385.gif', 1, 0, 0, '2026-01-25 15:22:41', '2026-02-09 17:28:52', 50),
(11, NULL, 'ลัลล์ไม่ชอบไวน์ Enemies With Benefits', 'เสือดาวหิมะ', NULL, NULL, 1, 339.00, 54, 'จากคนที่ไม่ถูกชะตากัน แต่ด้วยเหตุไม่คาดฝันทำให้เราสองคนเผลอมีความสัมพันธ์อันแสนลึกซึ้งที่ยากจะลืม ข้อตกลงลับๆ ระหว่างเราจึงได้เกิดขึ้น\r\n\r\nและฉันมั่นใจว่าจะไม่มีวันตกหลุมรักคุณเป็นอันขาด เพราะความสัมพันธ์ที่มาจากความปรารถนา ไม่มีทางจะพัฒนาไปเป็นความรักได้หรอก', '6976356f3bb41.gif', 1, 0, 0, '2026-01-25 15:23:27', '2026-02-02 13:37:57', 0),
(12, NULL, 'WIND BREAKER เล่ม 21 (ฉบับการ์ตูน)', 'ซาโตรุ นิอิ', NULL, NULL, 2, 80.00, 79, '\"เมื่อศึกตัดสินระหว่างอุเมมิยะกับทาคิอิชิจบลง\r\nโบฟูรินก็กลับมาสงบสุข และจัดงานเลี้ยงขอบคุณ\r\nโดยเชิญกลุ่มชิชิโท รวมถึงคนอื่นๆที่รีบรุดมาช่วย\r\nให้มาร่วมงานด้วย ในงานนั้นซาโกะได้เผชิญหน้า\r\nกับความผิดพลาดของตนและเผยความรู้สึก\r\nที่เก็บงำไว้ในใจให้ฮิอิรากิรับรู้ ส่วนนิเรอิ สุโอ\r\nและพวกเพื่อนร่วมชั้นของซากุระก็ตั้งปณิธาน\r\nขึ้นใหม่ในใจ ทว่าสุกิชิตะกลับรู้สึกสับสน\"', '69763632921a9.gif', 1, 0, 0, '2026-01-25 15:26:42', '2026-01-25 15:43:12', 0),
(13, NULL, 'Slime เกิดใหม่ทั้งทีก็เป็นสไลม์ไปซะแล้ว 28 (ฉบับการ์ตูน)', 'Fuse / Taiki Kawakami / Mitz Vah', NULL, NULL, 2, 129.00, 46, 'มาริอาเบล เริ่มรู้สึกถึงภัยคุกคามจากเทมเพสต์ที่กำลังขยายอำนาจ\r\nอย่างรวดเร็วและดูท่าว่าจะเติบโตอย่างต่อเนื่องในอนาคต\r\nเธอจึงเชิญ ยูคิ และหนึ่งในห้ายอดอาวุโส โยฮัน โรสเทีย\r\nมาร่วมในการเจรจาลับ โดยมีเป้าหมายคือการกำจัดเทมเพสต์...\r\nอีกด้านหนึ่ง ริมุรุที่กำลังมีความสุขจากความสำเร็จอย่างถล่มทลาย\r\nของดันเจี้ยนก็ได้รับคำเชิญให้เข้าร่วมการประชุมเพื่อตัดสินว่า\r\nเทมเพสต์สมควรเข้าร่วมคอนซิล ออฟ เวสต์หรือไม่\r\nริมุรุตัดสินใจว่าจะร่วมประชุม ทว่า การประชุมครั้งนี้คงไม่ง่ายดายเป็นแน่-', '6976365c03b25.gif', 1, 0, 0, '2026-01-25 15:27:24', '2026-02-09 16:12:48', 0),
(14, NULL, 'เกมสารภาพรักนี้น่ะ เรามาจบมันกันเถอะ 1', 'Yuki Domoto', NULL, NULL, 2, 80.00, 100, 'เพื่อนสมัยเด็กที่ใจตรงกัน\r\nยิ่งเข้าใกล้ก็ยิ่งรู้สึกรักยิ่งใกล้ชิดก็ยิ่งพูดไม่ออก\r\nสิ่งที่เชื่อมต่อทั้งสองคนที่ปากไม่ตรงกับใจก็คือ...\r\n\"เกมบอกรัก\" ที่เริ่มต้นมาตั้งแต่ตอนเด็กๆ\r\n\"ใครอายม้วนก่อนแพ้\"เกมที่ดูเหมือนเด็กๆ เล่นกัน\r\nแต่กลับอัดแน่นไปด้วยความรู้สึกยากจะเอ่ย\r\nการต่อสู้ที่ไม่อาจยอมแพ้ได้เริ่มต้นขึ้นแล้ว', '697636841f0a5.gif', 1, 0, 0, '2026-01-25 15:28:04', '2026-02-08 20:30:29', 0),
(15, NULL, 'มหาเวทย์ผนึกมาร โรงเรียนเฉพาะทางไสยศาสตร์นครโตเกียว เล่ม 0', 'Gege Akutami', NULL, NULL, 2, 90.00, 77, 'มหาเวทย์ผนึกมาร เล่ม 0 - ความมืดอันเจิดจ้า\r\nJujutsu Kaisen\r\nSorcery Fight\r\n\r\n\r\nอคคทสึ ยูตะเป็นนักเรียนมัธยมปลายที่ต้องการประหารตัวเอง เขาทนทุกข์ทรมานกับริกะวิญญาณแค้นที่สิงตนเองอยู่ แล้วในตอนนั้นเองโกะโจ ซาโตรุ ครูของ “โรงเรียนเฉพาะทางไสยศาสตร์นครโตเกียว” โรงเรียนที่ร่ำเรียนการปัดเป่า “คำสาป” ก็จับเขาย้ายเข้าโรงเรียน...!? เรื่องราวในอดีตที่เชื่อมโยงไปถึง “มหาเวทย์ผนึกมาร” เริ่มต้นขึ้นแล้ว!\r\n\r\n\r\nสารบัญ : มหาเวทย์ผนึกมาร โรงเรียนเฉพาะทางไสยศาสตร์นครโตเกียว เล่ม 0\r\nตอนที่ 1 เด็กต้องสาป\r\nตอนที่ 2 ดำมืด\r\nตอนที่ 3 ลงทัณฑ์ผู้อ่อนแอ\r\nตอนสุดท้าย ความมืดอันเจิดจ้า\r\n', '697636a64f28e.gif', 1, 0, 0, '2026-01-25 15:28:38', '2026-01-25 15:28:38', 0),
(16, NULL, 'AI กับการศึกษา พลิกโฉมสื่อการเรียนรู้ในยุคดิจิทัล', 'Wannapa Kwanson', NULL, NULL, 3, 122.00, 22, '', '697637572af1c.gif', 1, 0, 0, '2026-01-25 15:31:35', '2026-02-01 12:31:17', 0),
(17, NULL, 'สังคมแห่งปัญญา อนาคตใหม่ของประเทศไทย', 'กดี จำนงค์', NULL, NULL, 3, 226.00, 33, '\"ประเทศจะพัฒนาได้แค่ไหนขึ้นอยู่กับว่าประชาชนเรียนรู้ได้ไกลแค่ไหน\"\r\n\r\nในยุคที่โลกเปลี่ยนเร็วกว่าโรงเรียนจะปรับหลักสูตร\r\nและข้อมูลล้นหลามเกินกว่าที่จะวัดด้วยคะแนน\r\nเราจำเป็นต้องออกแบบประเทศไทยใหม่บนฐานของ \"ปัญญา\"\r\n\r\nหนังสือเล่มนี้จะพาคุณสำรวจคำตอบสำคัญว่า\r\nทำไมการเรียนรู้จึงไม่ใช่เรื่องของเด็ก\r\nทำไมความรู้ไม่ควรถูกผูกขาด\r\nและทำไมประเทศไทยจะไม่หลุดพ้นจากความเหลื่อมล้ำ\r\nหากเราไม่กล้าสร้าง สังคมแห่งปัญญา อย่างแท้จริง\r\n\r\nจากฟินแลนด์ถึงญี่ปุ่น\r\nจากโรงเรียนชนบทถึงเมืองแห่งการเรียนรู้\r\nจากพุทธธรรมถึงเทคโนโลยี\r\nคุณจะได้พบ \"แนวทางใหม่\" ที่พาเราออกจากความล้าหลัง\r\nสู่ อนาคตที่ประชาชนคือผู้มีพลังแห่งการเปลี่ยนแปลง\r\n\r\nนี่ไม่ใช่แค่หนังสือแต่นี่คือแผนที่ของประเทศไทยฉบับที่เราเป็นเจ้าของร่วมกัน\r\n', '6976377ccba75.gif', 1, 0, 0, '2026-01-25 15:32:12', '2026-01-25 15:32:12', 0),
(18, NULL, 'การเมืองระบอบทุน:ดับอนาคตการศึกษาไทย', 'ภักดี จำนงค์', NULL, NULL, 3, 127.00, 222, 'เรื่องย่อ\r\n\r\nหนังสือ \"การเมืองระบอบทุน ดับอนาคตการศึกษาไทย\" โดย ภักดี จำนงค์ เป็นงานวิเคราะห์เชิงลึกที่กล้าท้าทายความเชื่อเดิม ๆ ว่าระบบการศึกษาไทยล้มเหลวเพียงเพราะปัญหาเชิงบริหารหรือคุณภาพครู แต่กลับเสนอว่ารากของปัญหาแท้จริงคือ \"โครงสร้างอำนาจทางเศรษฐกิจและการเมืองที่ครอบงำการศึกษาไทยอย่างแนบเนียน\"\r\nเนื้อหาครอบคลุม 3 ภาคสำคัญ:\r\nภาคที่หนึ่ง วิเคราะห์กลไกทุนที่แทรกซึมในระบบการศึกษา ตั้งแต่นโยบายการศึกษา หลักสูตร งบประมาณ ไปจนถึงการครอบงำทางความคิด\r\nภาคที่สอง ชี้ให้เห็นผลพวงของระบอบทุนต่อเด็ก ครู และสถาบันการศึกษา โดยเฉพาะการสร้างความเหลื่อมล้ำที่ถาวร\r\nภาคสุดท้าย เสนอแนวทาง \"ปลดปล่อยการศึกษา\" จากอิทธิพลทุน ผ่านแนวคิดประชาธิปไตยทางการศึกษา โรงเรียนของประชาชน และการสร้างนโยบายเพื่อความเสมอภาคอย่างแท้จริง\r\nหนังสือเล่มนี้มุ่ง \"กระตุกต่อมความคิด\" ของผู้อ่านทุกกลุ่ม ไม่ว่าจะเป็นครู นักเรียน ผู้บริหาร นักการศึกษา นักการเมือง หรือประชาชนทั่วไป ให้มองการศึกษาในมุมที่ลึกและเชื่อมโยงกับโครงสร้างอำนาจของสังคมอย่างรอบด้าน\r\n\r\nด้วยภาษาคมชัด ท่าทีตรงไปตรงมา และการวิเคราะห์จากประสบการณ์ตรงของผู้เขียน \"การเมืองระบอบทุน ดับอนาคตการศึกษาไทย\" จึงเป็นมากกว่าหนังสือ หากแต่เป็น \"คำประกาศ\" ว่า ถึงเวลาที่เราต้องหยุดยอมรับการศึกษาที่ถูกทุนควบคุม และเริ่มสร้างอนาคตใหม่ด้วยมือของเราเอง', '697637a32b4c3.gif', 1, 0, 0, '2026-01-25 15:32:51', '2026-01-25 15:32:51', 0),
(19, NULL, 'การเงินธุรกิจ 101 สำหรับเจ้าของธุรกิจขนาดเล็ก', 'ศศิเพ็ญ พงษ์สมบูรณ์', NULL, NULL, 4, 234.00, 766, '**Corporate Finance 101 for Small Business**\r\n\r\nธุรกิจไม่ได้พังเพราะไม่เก่ง\r\nแต่พังเพราะตัดสินใจไปเรื่อย ๆ\r\nโดยไม่รู้ว่ากำลังแลกอะไรอยู่\r\n\r\nหนังสือเล่มนี้ไม่ใช่ตำราการเงิน\r\nไม่สอนสูตร\r\nไม่แจกทางลัด\r\nและไม่พยายามทำให้คุณ \"ทำได้เองทุกอย่าง\"\r\n\r\nแต่จะพาคุณเข้าใจว่า\r\n**การเงินองค์กรคือภาษาในการตัดสินใจของเจ้าของกิจการ**\r\n\r\nตั้งแต่วันที่เงินธุรกิจเริ่มไม่ใช่เงินส่วนตัว\r\nวันที่กำไรยังมี แต่เงินสดเริ่มตึง\r\nวันที่ธุรกิจโตขึ้น แต่การตัดสินใจยากขึ้น\r\nจนถึงวันที่ต้องเลือกว่าจะ \"โตต่อ พอแค่นี้ หรือเปลี่ยนเกม\"\r\n\r\nหนังสือเล่มนี้จะช่วยให้คุณ\r\n– มองงบการเงินเป็นภาพรวม ไม่ใช่แค่ตัวเลข\r\n– เข้าใจว่าเงินแต่ละก้อนควรทำหน้าที่อะไร\r\n– เห็น trade-off ของการเติบโต ความมั่นคง และเงินสด\r\n– และตัดสินใจทางการเงินอย่างซื่อสัตย์กับความจริงของธุรกิจตัวเอง\r\n\r\nเหมาะสำหรับ\r\nเจ้าของกิจการ SME / Family Business\r\nผู้บริหารที่ไม่ได้จบการเงิน แต่ต้องตัดสินใจเรื่องเงินทุกวัน\r\nและคนทำธุรกิจที่ไม่อยากเรียนรู้บทเรียนราคาแพงจากความผิดพลาดเดิม ๆ\r\n\r\nนี่ไม่ใช่หนังสือที่ทำให้คุณรู้สึกเก่งขึ้น\r\nแต่เป็นหนังสือที่ทำให้คุณ\r\n**คิดผิดยากขึ้น**', '697637f6b8887.gif', 1, 0, 0, '2026-01-25 15:34:14', '2026-01-25 15:34:14', 0),
(20, NULL, 'CEO : Can Do Everything Officer เจ้าของธุรกิจยุค AI รวยได้ด้วยตัวคนเดียว', 'พิธาน ธนเศรษฐทรัพย์', NULL, NULL, 4, 333.00, 876, 'การพูดไม่ใช่แค่การสื่อสาร แต่คือพลัง ที่ผู้นำใช้เพื่อกำหนดไม่ต้องรอเงินทุน ไม่ต้องรอทีม และไม่ต้องรอเวลาเพราะวันนี้ \"เครื่องมืออัจฉริยะ\" พร้อมทำงานให้คุณทุกตำแหน่งแล้ว CEO : Can Do Everything Officer จะเปลี่ยนวิธีคิดของคุณ จาก \"พนักงาน\" สู่ \"เจ้าของบริษัทที่ใช้ AI ทำงานแทน\" Mindset ใหม่ของเจ้าของธุรกิจยุค AI เปลี่ยนจาก \"ทำทุกอย่างเอง\" เป็น \"ออกแบบระบบให้ AI ทำแทน\" AI Stack ที่ครบที่สุดสำหรับเจ้าของธุรกิจ จาก ChatGPT จนถึง Gemini, Midjourney, Runway, Canva AI, และ Perplexity พร้อมแนวทางใช้จริงในแต่ละตำแหน่ง ตั้งแต่เลขา นักวิเคราะห์ ไปถึงทีมการตลาดระบบ Workflow อัตโนมัติ ที่รันได้ 24 ชั่วโมงสั่งให้ AI เขียน คิด วิเคราะห์ ออกแบบ และขายได้ต่อเนื่องแม้คุณจะพัก งานยังเดินต่อ และรายได้ยังเติบโตขยายรายได้โดยไม่ต้องขยายทีมใช้ AI เป็นแรงขับเคลื่อนธุรกิจแบบยั่งยืน ลดต้นทุน เพิ่มกำไร และสร้างระบบที่โตเองได้โลกธุรกิจยุคใหม่ไม่ได้ต้องการคนที่ทำทุกอย่างด้วยตัวเองแต่ต้องการคนที่ \"รู้จักใช้ AI ทำทุกอย่างให้เกิดขึ้นจริง\r\n', '6976382abf63f.gif', 1, 0, 0, '2026-01-25 15:35:06', '2026-01-25 15:36:37', 0),
(21, NULL, 'Angel Investor 101 ก้าวแรกสู่การลงทุนในสตาร์ทอัพ หนังสือเล่มแรกในไทยที่อธิบายการเป็นนักลงทุนเทวดา', 'ดร.วศ.สิริพงศ์ จึงถาวรรณ และคณะ', NULL, NULL, 4, 1499.00, 55, 'ANGEL INVESTOR 101 : ก้าวแรกสู่การลงทุนในสตาร์ทอัพ\r\nหนังสือเล่มนี้ไม่ได้สอนให้คุณ \"รวยเร็ว\"\r\nแต่สอนให้คุณ ไม่พังตั้งแต่ดีลแรก\r\n\r\nโดย สมาคมการค้าทีบาน (TBAN)\r\n\r\nAngel Investor คือการลงทุนที่โอกาสสูง\r\nแต่ความเสี่ยงก็สูงไม่แพ้กัน\r\nคนที่แพ้ ไม่ใช่คนไม่มีเงิน\r\nแต่คือคนที่ ลงทุนด้วยความหวัง แทนที่จะลงทุนด้วยระบบ\r\n\r\nหนังสือเล่มนี้จะพาคุณเข้าใจโลกของ Angel Investor แบบเป็นขั้นเป็นตอน\r\nตั้งแต่ Mindset ที่ถูกต้อง, โครงสร้างการลงทุน,\r\nไปจนถึง วิธีคิดแบบนักลงทุนตัวจริง ไม่ใช่นักเสี่ยงโชค\r\n\r\nคุณจะได้เรียนรู้ว่า\r\n- Angel Investor ต่างจาก VC และนักลงทุนหุ้นอย่างไร\r\n- ทำไมสตาร์ทอัพส่วนใหญ่ \"ล้ม\" และคุณควรหลบตรงไหน\r\n- ลงทุนอย่างไรให้ ขาดทุนได้จำกัด แต่ โอกาสเติบโตเปิดกว้าง\r\n- การดูทีม ผู้ก่อตั้ง โมเดลธุรกิจ และตัวเลขที่ \"โกหกเก่ง\"\r\n- เปลี่ยนการลงทุนจากความหวัง ให้กลายเป็นกระบวนการตัดสินใจ\r\n\r\nหนังสือเล่มนี้เขียนจากประสบการณ์ตรง\r\nทั้งฝั่ง นักลงทุน ที่ปรึกษา และผู้ประเมินธุรกิจ\r\nไม่ใช่ตำราทฤษฎี และไม่ใช่แรงบันดาลใจลอย ๆ\r\n\r\nถ้าคุณกำลังคิดจะเป็น Angel Investor\r\nเล่มนี้คือ คู่มือเริ่มต้นที่ควรอ่าน ก่อนจ่ายเงินจริง\r\n\r\nหนังสือเล่มนี้เหมาะกับใคร\r\n- ผู้ที่สนใจลงทุนในสตาร์ทอัพ แต่ไม่รู้จะเริ่มอย่างไร\r\n- ผู้ประกอบการ นักลงทุน และผู้บริหาร ที่อยากก้าวสู่บทบาท Angel Investor\r\n- ผู้ที่ต้องการลดความเสี่ยง และเข้าใจ \"เกมการลงทุน\" อย่างเป็นระบบ\r\n\r\nลงทุนได้ไม่ยาก\r\nแต่ลงทุนให้รอด ต้องมีวิธีคิดที่ถูกต้อง\r\n', '6976385db3004.gif', 1, 0, 0, '2026-01-25 15:35:57', '2026-01-25 15:42:56', 0),
(22, NULL, 'ฉากสำคัญ \"พระเจ้าตาก\" ประวัติศาสตร์ที่ต้องเล่า', 'ปรามินทร์ เครือทอง', NULL, NULL, 7, 219.00, 88, 'แม้ “ประวัติศาสตร์กรุงธนบุรี” จะมีตอนจบอย่างสมบูรณ์ไปแล้ว แต่ “พระราชประวัติพระเจ้าตาก” ยังไม่จบ “ปรามินทร์ เครือทอง” กลับมาอีกครั้ง พร้อมหนังสือเล่มใหม่ล่าสุดคือ ฉากสำคัญ “พระเจ้าตาก” ประวัติศาสตร์ที่ต้องเล่า ซึ่งจะพาผู้อ่านไปไขปริศนา “9 ฉากสำคัญ”ของพระเจ้าตาก พร้อมชวนมองความสำคัญของพระองค์ในฐานะกษัตริย์ “สามัญชน” โดยไม่จำเป็นต้องสรรหาเรื่องราว “ยอพระเกียรติ” ให้เกินเลยไปจากพงศาวดารแม้แต่น้อย', '6976394cb4522.gif', 1, 0, 0, '2026-01-25 15:39:56', '2026-01-25 15:39:56', 0),
(23, NULL, '36ชีวประวัติบุคคลสำคัญที่มีอิทธิพลต่อโลก', 'กฤษกรณ์ อุตมะ', NULL, NULL, 7, 279.00, 98, '\"แรงบันดาลใจจากชีวิตจริงของ 36 บุคคลสำคัญ\r\nที่ได้ใช้ความมุมานะ วิสัยทัศน์ และหัวใจอันแข็งแกร่ง ก้าวข้ามทุกข้อจำกัด\r\nเพื่อสร้างความเปลี่ยนแปลงให้กับโลก ไม่ว่าพวกเขาหรือเธอจะมาจากเชื้อชาติอะไร\r\nเพศอะไร หรือตำแหน่งอะไร เรื่องราวเหล่านี้จะปลุกไฟและแรงบันดาลใจในตัวคุณ เพื่อให้คุณได้เห็นว่า\r\nทุกคนสามารถส่งอิทธิพลและฝากร่องรอยไว้ในหน้าประวัติศาสตร์ได้เช่นกัน.\"', '6976397a77609.gif', 1, 0, 0, '2026-01-25 15:40:42', '2026-01-25 15:40:42', 0),
(24, NULL, 'Travel Talk บทสนทนาสำหรับการท่องเที่ยว', 'ผู้แต่ง Li Shujuan แปล ไอรีน เป', NULL, NULL, 8, 196.00, 999, 'ฝึกพูดภาษาจีนอย่างง่ายๆ หมวด บทสนทนาภาษาจีนสำหรับการท่องเที่ยว', '697639d05d24d.gif', 1, 0, 0, '2026-01-25 15:42:08', '2026-01-25 15:42:08', 0),
(25, NULL, 'มีสติหน่อยคุณธีร์', 'ลวิฬาร์ (laWila)', NULL, NULL, 1, 389.00, 566, '\"คนอย่างฉัน อยากได้อะไรก็ต้องได้\"\r\n\r\nคำประกาศกร้าวจาก \'คุณธีร์\' นักธุรกิจหนุ่มลูกครึ่งไทย - รัสเซีย\r\nหล่อ รวย มาดพระเอกนิยายในอุดมคติ\r\n\r\nทำเอา \'พีช\' ช่างภาพหนุ่มผู้ใช้ชีวิตเรียบง่ายมาตลอดได้แต่กรีดร้องว่า\r\n\r\nมีสติหน่อยคุณธีร์!!\r\n\r\nเป็นที่ปรึกษาจำเป็นให้มาเฟียนี่มันไม่ใช่เรื่องง่ายเลย...\r\n\r\nให้ปรึกษากันไปมา ไม่รู้ทำไมนะ\r\nถึงได้กลายเป็นคนที่คุณธีร์ให้ความสนใจแทน\r\n\r\n#มีสติหน่อยคุณธีร์ ฝากกดรีวิวเป็นกำลังใจให้ด้วยนะคะ\r\n', '69763b2923429.gif', 1, 0, 0, '2026-01-25 15:47:53', '2026-01-25 16:58:14', 0),
(26, NULL, 'BUILT IN LOVE #ก่อร่างสร้างเลิฟ', 'แซลม่อน', NULL, NULL, 1, 289.00, 78, 'ตัญติญา สถาปนิกคนเก่งที่ต้องโคจรมาพบคู่ปรับในเรื่องงานอย่าง\r\nเรนิตา ซินแสฮวงจุ้ยมือทองที่เชี่ยวชาญเรื่องบ้านให้อยู่แล้วเฮงๆ รวยๆ\r\nงานนี้ศึกหนักจึงเริ่มต้นขึ้น เหมือนลิ้นกับฟัน น้ำกับไฟ หยินกับหยาง\r\nกับความสัมพันธ์ที่แตกต่างกันสุดขั้ว จากบ้านที่ต้องก่อสร้าง\r\nหรือจะกลายเป็นพื้นที่ว่างในหัวใจที่มีความรักขึ้นมาก่อตัว?', '69763b566ca54.gif', 1, 0, 0, '2026-01-25 15:48:38', '2026-02-09 16:52:28', 0),
(27, NULL, 'ฤดูหลงป่า', 'theneoclassic', NULL, NULL, 1, 279.00, 84, '\'ฟีฟ่า\' เด็กหนุ่มม.ปลายวัยเตรียมเข้ามหา\'ลัย ได้รับหน้าที่ (แบบไม่เต็มใจ) ให้ไปดูแลคุณยายที่ต่างจังหวัดช่วงก่อนเปิดเทอม เจ้าตัวทำได้เพียงยอมจำนนแต่ก็ยื่นข้อเสนอว่าการไปดูแลยายครั้งนี้ถือเป็นการฝึกใช้ชีวิตเพื่อเตรียมอยู่หอ เพราะทำเลบ้านคุณยายนั้นช่างทุรกันดารซะเหลือเกิน ที่นั่นเขาได้พบชายหนุ่มรุ่นคุณน้าอย่าง \'หัวหน้าเหม\' หรือในชื่อที่รู้กันแค่สองคนว่า \'พี่เติร์ด\' ของน้องฟีฟ่า เจ้าหน้าที่กรมอุทยานฯ ผู้ซึ่งความรักไม่รุ่งมุ่งแต่งานจนไม่คิดจะมองใคร วันๆ อยู่แต่กับเหล่าสิงสาราสัตว์และสีเขียวของป่าดงพงไพร การปรากฏตัวของเด็กชายน่ารักสดใสอย่างฟีฟ่าทำให้โลกของเขาเปลี่ยนไป เผลอเปิดประตูใจให้คนเป็นน้องเข้ามาแต่งแต้มสีสัน ให้ฤดูร้อนกลายเป็นฤดูรักอันน่าหลงใหลและอบอุ่นหัวใจ', '69763b7ce3b99.gif', 1, 0, 0, '2026-01-25 15:49:16', '2026-02-03 09:43:23', 0),
(28, NULL, 'ขนมปังชีสเนย Bake love feelings', 'เสือดาวหิมะ', NULL, NULL, 1, 339.00, 7, 'ถึงพวกเราจะเริ่มต้นกันไม่ดีเท่าไหร่ แต่ฉันก็หวังว่าจะสนิทและสามารถร่วมงานกับคุณได้อย่างราบรื่น ไม่รู้ว่าเพราะความได้ใกล้ชิดกันหรือเปล่าถึงทำให้ฉันรู้สึกใจเต้นมากกว่าปกติ\r\n\r\nคงต้องคอยบอกตัวเองไว้ว่าความใจดีที่คุณมอบให้มันเป็นเพราะหน้าที่เท่านั้น อย่าไปคิดจริงจังเป็นอันขาด', '69763ba036f28.gif', 1, 0, 0, '2026-01-25 15:49:52', '2026-02-09 18:04:29', 0),
(29, NULL, 'THE SOUL CASES สายสืบอาชญาณ', 'สามดอกจิก', NULL, NULL, 1, 385.00, 662, '\"เมื่อกฎหมายมนุษย์ไม่อาจพิพากษาความชั่ว\r\nเธอจึงต้องมีพลังจากโลกวิญญาณช่วยหนุนหลัง\r\n\r\n‘ร้อยตำรวจเอกญาณาธร’ ตำรวจหญิงผู้มีสัมผัสพิเศษ\r\nเธอต้องนำทีมสืบสวนที่ไม่มีใครต้องการ\r\nมาคลี่คลายคดีฆาตกรรมซับซ้อนที่เชื่อมโยงกับไสยศาสตร์มืด\r\nท่ามกลางอดีตรักที่หวนคืนราวกับเป็นบททดสอบจากอดีตชาติ\r\n‘ร้อยตำรวจโทสุมิตรา’ คือจุดเปลี่ยนทั้งในงานสืบสวนและหัวใจของญาณาธร\r\nขณะที่ภัยร้ายกลับยิ่งคืบคลาน ทั้งสองต้องเร่งไขเงื่อนงำให้กระจ่าง\r\nก่อนที่ความมืดจะพรากชีวิตของผู้บริสุทธิ์ไปมากกว่านี้!\"', '697f9a719cad6.gif', 1, 0, 0, '2026-02-01 18:24:49', '2026-02-08 20:27:40', 20);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'นิยาย', 'หนังสือนิยายทั่วไป', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(2, 'การ์ตูน', 'หนังสือการ์ตูนและมังงะ', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(3, 'การศึกษา', 'หนังสือเรียนและหนังสืออ้างอิง', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(4, 'ธุรกิจ', 'หนังสือธุรกิจและการบริหาร', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(5, 'เทคโนโลยี', 'หนังสือคอมพิวเตอร์และเทคโนโลยี', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(6, 'จิตวิทยา', 'หนังสือจิตวิทยาและพัฒนาตนเอง', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(7, 'ประวัติศาสตร์', 'หนังสือประวัติศาสตร์', '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(8, 'ท่องเที่ยว', 'หนังสือท่องเที่ยวและไกด์บุ๊ก', '2026-01-25 06:21:19', '2026-01-25 06:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `payment_method` enum('transfer','cod','credit_card','promptpay') DEFAULT 'transfer',
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `shipping_name` varchar(100) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_province` varchar(100) DEFAULT NULL,
  `shipping_postal_code` varchar(10) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tracking_number` varchar(50) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `user_id`, `total_amount`, `status`, `slip_image`, `payment_method`, `payment_status`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_province`, `shipping_postal_code`, `note`, `paid_at`, `shipped_at`, `delivered_at`, `created_at`, `updated_at`, `tracking_number`, `order_date`, `phone_number`, `customer_name`, `address`, `phone`, `shipping_fee`) VALUES
(1, 'ORD20260124001', 2, 605.00, '🚚 จัดส่งแล้ว', NULL, 'transfer', 'paid', 'สมชาย ใจดี', '0823456789', '456 ถนนพหลโยธิน', 'กรุงเทพมหานคร', '10400', NULL, NULL, NULL, NULL, '2026-01-25 06:21:19', '2026-02-03 10:05:04', 'FLASH63908956', '2026-02-08 16:35:14', NULL, NULL, NULL, NULL, 0.00),
(2, 'ORD20260124002', 3, 355.00, 'shipped', NULL, 'cod', 'unpaid', 'สมหญิง รักหนังสือ', '0834567890', '789 ถนนลาดพร้าว', 'กรุงเทพมหานคร', '10310', NULL, NULL, NULL, NULL, '2026-01-25 06:21:19', '2026-01-25 08:15:05', NULL, '2026-02-08 16:35:14', NULL, NULL, NULL, NULL, 0.00),
(20, 'ORD-2602091712-58', 11, 389.00, '🚚 จัดส่งแล้ว', NULL, 'cod', 'unpaid', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-09 16:12:10', '2026-02-09 16:15:26', 'FLASH33255544', '2026-02-09 23:12:10', NULL, 'winrinapenfangun', 'winrina', '0647825093', 0.00),
(21, 'ORD-2602091712-16', 11, 149.50, '✅ สำเร็จ', 'slip_698a076bbcc00.jpg', '', 'unpaid', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-09 16:12:27', '2026-02-09 16:15:10', '', '2026-02-09 23:12:27', NULL, 'winrinapenfangun', 'winrina', '0647825093', 0.00),
(22, 'ORD-2602091712-92', 11, 179.00, 'ยกเลิก', NULL, 'cod', 'unpaid', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-09 16:12:43', '2026-02-09 16:12:48', NULL, '2026-02-09 23:12:43', NULL, 'winrinapenfangun', 'winrina', '0647825093', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `book_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 1, 2, 285.00, 570.00),
(2, 1, 3, 1, 75.00, 75.00),
(27, 20, 28, 1, 339.00, 0.00),
(28, 21, 10, 1, 149.50, 0.00),
(29, 22, 13, 1, 129.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_logs`
--

CREATE TABLE `order_logs` (
  `log_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_logs`
--

INSERT INTO `order_logs` (`log_id`, `order_id`, `old_status`, `new_status`, `changed_by`, `created_at`) VALUES
(1, 15, 'cancelled', '🚚 จัดส่งแล้ว', 'admin', '2026-02-02 14:29:28'),
(2, 15, '🚚 จัดส่งแล้ว', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-02 14:29:37'),
(3, 15, '🔵 ชำระเงินแล้ว', '✅ สำเร็จ', 'admin', '2026-02-02 14:29:45'),
(4, 14, 'shipped', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-02 14:30:41'),
(5, 13, 'pending', '✅ สำเร็จ', 'admin', '2026-02-02 14:30:48'),
(6, 13, '✅ สำเร็จ', '🚚 จัดส่งแล้ว', 'admin', '2026-02-02 14:30:56'),
(7, 15, '✅ สำเร็จ', '🟡 รอตรวจสอบ', 'admin', '2026-02-02 14:32:53'),
(8, 15, '🟡 รอตรวจสอบ', '❌ ยกเลิก', 'admin', '2026-02-02 14:32:57'),
(9, 15, '❌ ยกเลิก', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-02 14:44:19'),
(10, 15, '🔵 ชำระเงินแล้ว', '✅ สำเร็จ', 'admin', '2026-02-02 14:44:24'),
(11, 15, '✅ สำเร็จ', '🚚 จัดส่งแล้ว', 'admin', '2026-02-02 14:44:29'),
(12, 15, '🚚 จัดส่งแล้ว', '✅ สำเร็จ', 'admin', '2026-02-02 14:44:32'),
(13, 13, '🚚 จัดส่งแล้ว', '❌ ยกเลิก', 'admin', '2026-02-02 14:49:05'),
(14, 13, '❌ ยกเลิก', '🚚 จัดส่งแล้ว', 'admin', '2026-02-02 14:49:12'),
(15, 14, '🔵 ชำระเงินแล้ว', '🚚 จัดส่งแล้ว', 'admin', '2026-02-02 15:04:02'),
(16, 13, '🚚 จัดส่งแล้ว', '🟡 รอตรวจสอบ', 'admin', '2026-02-03 09:19:28'),
(17, 15, '✅ สำเร็จ', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-03 09:19:40'),
(18, 13, '🟡 รอตรวจสอบ', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-03 09:43:23'),
(19, 14, '🚚 จัดส่งแล้ว', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-03 09:46:46'),
(20, 13, '🔵 ชำระเงินแล้ว', '🚚 จัดส่งแล้ว', 'admin', '2026-02-03 09:49:04'),
(21, 1, 'pending', '🚚 จัดส่งแล้ว', 'admin', '2026-02-03 10:05:04'),
(22, 13, '🚚 จัดส่งแล้ว', '🟡 รอตรวจสอบ', 'admin', '2026-02-03 10:05:26'),
(23, 19, 'pending', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-08 21:00:14'),
(24, 19, '🔵 ชำระเงินแล้ว', '✅ สำเร็จ', 'admin', '2026-02-08 21:00:19'),
(25, 21, 'pending', '✅ สำเร็จ', 'admin', '2026-02-09 16:15:10'),
(26, 20, 'pending', '🔵 ชำระเงินแล้ว', 'admin', '2026-02-09 16:15:22'),
(27, 20, '🔵 ชำระเงินแล้ว', '🚚 จัดส่งแล้ว', 'admin', '2026-02-09 16:15:26'),
(28, 23, 'pending', '✅ สำเร็จ', 'admin', '2026-02-09 16:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `province`, `postal_code`, `role`, `profile_image`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer1@email.com', 'สมชาย ใจดี', '0823456789', '456 ถนนพหลโยธิน', 'กรุงเทพมหานคร', '10400', 'customer', NULL, 1, '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(3, 'customer2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer2@email.com', 'สมหญิง รักหนังสือ', '0834567890', '789 ถนนลาดพร้าว', 'กรุงเทพมหานคร', '10310', 'customer', NULL, 1, '2026-01-25 06:21:19', '2026-01-25 06:21:19'),
(9, 'samuraijack', '$2y$10$aYg/kHnbY31SY/CeJe9YL.CNwW.KuKGb4nxgFJcoov0bJuP91wsxi', 'samuraijack.mj@gmail.com', 'winrinapenfangun', '0647825093', 'winrina', NULL, NULL, 'customer', 'profile_9_1769350169.jpg', 1, '2026-01-25 13:41:55', '2026-02-08 19:43:46'),
(10, 'jimin', '$2y$10$qNPWsmm8fkL5wZTmF/xKHOuCal7NkhwTsHzGTRixErvuskLv.vmS.', 'jiman@gmail.com', 'test', NULL, NULL, NULL, NULL, 'customer', NULL, 1, '2026-02-01 19:13:39', '2026-02-01 19:13:39'),
(11, 'gokart', '$2y$10$Zw/Ma547a3ItmS56DWBD8eiocodTylmZWNB/HvBmElA3t2.y0iMdW', 'gokartitsme@gmail.com', 'winrinapenfangun', '0647825093', 'winrina', NULL, NULL, 'customer', 'profile_11_1770653417.jpg', 1, '2026-02-09 16:08:03', '2026-02-09 16:10:32'),
(12, 'admin', '$2y$10$AAUoNj4S3DND6QowtUmwjeioc9GOjevAc1XlPYwYSVzGMTOn9Fc3.', 'admin@test.com', 'winrinapenfangun', '0647825093', 'winrina', NULL, NULL, 'admin', 'profile_12_1770676758.jpg', 1, '2026-02-09 22:31:24', '2026-02-09 22:39:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_books_with_category`
-- (See below for the actual view)
--
CREATE TABLE `vw_books_with_category` (
`book_id` int(11)
,`isbn` varchar(20)
,`title` varchar(255)
,`author` varchar(255)
,`publisher` varchar(255)
,`publication_year` year(4)
,`price` decimal(10,2)
,`stock_quantity` int(11)
,`image` varchar(255)
,`is_bestseller` tinyint(1)
,`is_new` tinyint(1)
,`category_name` varchar(100)
,`category_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_book_sales_stats`
-- (See below for the actual view)
--
CREATE TABLE `vw_book_sales_stats` (
`book_id` int(11)
,`title` varchar(255)
,`author` varchar(255)
,`price` decimal(10,2)
,`total_sold` decimal(32,0)
,`total_revenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_orders_with_customer`
-- (See below for the actual view)
--
CREATE TABLE `vw_orders_with_customer` (
`order_id` int(11)
,`order_number` varchar(20)
,`total_amount` decimal(10,2)
,`status` varchar(50)
,`payment_method` enum('transfer','cod','credit_card','promptpay')
,`payment_status` enum('unpaid','paid','refunded')
,`created_at` timestamp
,`full_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `vw_books_with_category`
--
DROP TABLE IF EXISTS `vw_books_with_category`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_books_with_category`  AS SELECT `b`.`book_id` AS `book_id`, `b`.`isbn` AS `isbn`, `b`.`title` AS `title`, `b`.`author` AS `author`, `b`.`publisher` AS `publisher`, `b`.`publication_year` AS `publication_year`, `b`.`price` AS `price`, `b`.`stock_quantity` AS `stock_quantity`, `b`.`image` AS `image`, `b`.`is_bestseller` AS `is_bestseller`, `b`.`is_new` AS `is_new`, `c`.`category_name` AS `category_name`, `c`.`category_id` AS `category_id` FROM (`books` `b` left join `categories` `c` on(`b`.`category_id` = `c`.`category_id`)) WHERE `b`.`is_active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_book_sales_stats`
--
DROP TABLE IF EXISTS `vw_book_sales_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_book_sales_stats`  AS SELECT `b`.`book_id` AS `book_id`, `b`.`title` AS `title`, `b`.`author` AS `author`, `b`.`price` AS `price`, coalesce(sum(`oi`.`quantity`),0) AS `total_sold`, coalesce(sum(`oi`.`subtotal`),0) AS `total_revenue` FROM ((`books` `b` left join `order_items` `oi` on(`b`.`book_id` = `oi`.`book_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`order_id` and `o`.`status` <> 'cancelled')) GROUP BY `b`.`book_id`, `b`.`title`, `b`.`author`, `b`.`price` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_orders_with_customer`
--
DROP TABLE IF EXISTS `vw_orders_with_customer`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_orders_with_customer`  AS SELECT `o`.`order_id` AS `order_id`, `o`.`order_number` AS `order_number`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`payment_method` AS `payment_method`, `o`.`payment_status` AS `payment_status`, `o`.`created_at` AS `created_at`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone` FROM (`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_book` (`book_id`);

--
-- Indexes for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_book` (`book_id`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
