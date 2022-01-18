<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    	die("Connection failed: " . $conn->connect_error);
	}

$searchsql='Select * from products order by p_id asc';
$product_ids=array();

if(filter_input(INPUT_POST, 'addcart')){
	
	if(!isset($_SESSION['email']))
	{
		if(!isset($_SESSION['email'])){
			echo "<script>alert('You are not logged in');</script>";
			header("refresh:1;url=login.php");			
		}
	}

	else{
		if(isset($_SESSION['shopping_cart'])){
			
			//print_r($_SESSION['shopping_cart'][0]);
			$count=count($_SESSION['shopping_cart']);
			$product_ids=array_column($_SESSION['shopping_cart'], 'id');

			if(!in_array(filter_input(INPUT_POST, 'id'),$product_ids)){
				$_SESSION['shopping_cart'][$count]=array
				(
					'id'=> filter_input(INPUT_POST, 'id'),
					'name'=> filter_input(INPUT_POST, 'name'),
					'price'=> filter_input(INPUT_POST, 'price'),
					'quantity'=> filter_input(INPUT_POST, 'quantity'),
				);
			}
			else{
				for($i=0;$i<count($product_ids);$i++){
					if($product_ids[$i]==filter_input(INPUT_POST, 'id')){
						$_SESSION['shopping_cart'][$i]['quantity']+=filter_input(INPUT_POST, 'quantity');
					}
				}
			}
		}
		else{
			$_SESSION['shopping_cart'][0]=array
			(
				'id'=> filter_input(INPUT_POST, 'id'),
				'name'=> filter_input(INPUT_POST, 'name'),
				'price'=> filter_input(INPUT_POST, 'price'),
				'quantity'=> filter_input(INPUT_POST, 'quantity'),
			);

		}
	}
}
if(filter_input(INPUT_GET,'removecart'))
{	
	foreach ($_SESSION['shopping_cart'] as $key => $product) {
		if($product['id']==filter_input(INPUT_GET, 'id')){
			unset($_SESSION['shopping_cart'][$key]);
			break;
		}
	}
	$_SESSION['shopping_cart']=array_values($_SESSION['shopping_cart']);
}
if(isset($_POST['placeorder']))
{
	//SELECT * FROM orders WHERE m_id=1 ORDER BY o_id DESC LIMIT 1
	
	$m_id=$_SESSION['m_id'];
	$sql = "INSERT INTO `orders`(`m_id`,`date`) VALUES ($m_id,NOW())";

	if(mysqli_query($conn,$sql))
	{
		
		$res = mysqli_query($conn,"SELECT * FROM orders WHERE m_id=$m_id ORDER BY o_id DESC LIMIT 1");
		$row=mysqli_fetch_array($res,MYSQLI_ASSOC);
		$o_id=$row['o_id'];
		foreach ($_SESSION['shopping_cart'] as $key => $product) {
			$p=$product['quantity']*$product['price'];
			$quantity=$product['quantity'];
			$p_id=$product['id'];
			
			$sql = "INSERT INTO `orderproduct`(`o_id`, `p_id`, `quantity`, `total`) VALUES ('$o_id', '$p_id', '$quantity', '$p')";
			if(mysqli_query($conn,$sql)){
				$qua=mysqli_query($conn, "select quantity from products where p_id=$p_id");
				$rowqu=mysqli_fetch_array($qua,MYSQLI_ASSOC);
				$got=$rowqu['quantity'];
				$newQua=$got-$quantity;
				mysqli_query($conn,"update products set quantity=$newQua where p_id=$p_id");
				unset($_SESSION['shopping_cart'][$key]);

			}
		}
		
	}

}
if(isset($_GET['searchbtn']))
{	
	$val = $_GET['searchval'];
	$searchsql="SELECT * FROM `products` WHERE `name` LIKE '%$val%' ORDER BY `p_id` ASC";

}
if(isset($_GET['category']))
{
	$val=$_GET['category'];
	$searchsql="SELECT * FROM `products` WHERE `categories` LIKE '%$val%' ORDER BY `p_id` ASC";

}
?>


<!DOCTYPE html>
<html>
<head>
	<title>Restaurant Management System</title>
	<link type="text/css" rel="stylesheet"  href="menu.css">
</head>

<body>
	<div class="main"> 
		<nav>
			<img class="logo" src="banner_1.jpg"width="250"hieght="250">
			<UL>
				<li><a href="welcome.php">Home</a></li>
				<li><a href="menu.php">Menu</a></li>
				<li><a href="profile.php">Purchases</a></li>
				<?php if(isset($_SESSION['email'])){
						
						echo "
							<li><a href=".'"logout.php">Log Out'."</a></li>
						</UL>";
	
						
					}
					else{
					echo "<li><a href=".'"login.php">Login'."</a></li>
						</UL>";

					 	
					}
				?>
						
		</nav>
	</div>
	
	<div class="bodycontent">
		<div class="sidebar">
			<nav class="horizontal">
				<p class="header">Categories</p>	
				<UL>
					<li><a href="menu.php?category=fastfood">Fast Food</a></li>
					<li><a href="menu.php?category=meal">Set Menu</a></li>
					<li><a href="menu.php?category=desert">Desert</a></li>
					<li><a href="menu.php?category=beverage">Beverage</a></li>
				</UL>
			</nav>
		</div>
		
		<div class="content">
			 <div class="box search">
				<p>
					<form method="GET" action="">
						<input type="text" name="searchval" placeholder="Search for food items">
						<button class="searchbtn" name="searchbtn" >Search</button>
					</form>
				</p>
			</div>

			<?php 
				
				$query = $searchsql;
				$result=mysqli_query($conn,$query);
				
				if($result)
				{
					if(mysqli_num_rows($result)>0)
					{
						while($products=mysqli_fetch_assoc($result))
						{
							?>
							
							<div class="box box2"> <img class="product" src="images/<?php echo $products['images']; ?>"
								width="200" hieght="200">
								<p style="font-weight: bold; margin-left: 20px;font-size: 20px;"><?php echo $products['name']; ?></p>
								<p class="price">Price: <?php echo $products['price']; ?>/=</p>
								
								<form method="post" action="menu.php">
									<center><input type="number" name="quantity" value="1" min="0" style="width: 95%; border:2px solid ;margin: 0px;padding: 5px;"></center>
									<input type="hidden" name="name" value="<?php echo $products['name']; ?>">
									<input type="hidden" name="id" value="<?php echo $products['p_id']; ?>">
									<input type="hidden" name="price" value="<?php echo $products['price']; ?>">
									<!--<center><button class="addcart" type="submit" name="addcart">Add to cart</button></form></center>-->
									<center><input type="submit" name="addcart" value="Add to cart" ></center>
								</form>

							</div>
							<?php
						}
					}	
				}

			 ?>
			
		</div>
		<div class="cart">
			<div class="cartHead">
					Cart
			</div>
			<?php
				if(!empty($_SESSION['shopping_cart']))
				{
					$total=0;
					$count=0;
					foreach ($_SESSION['shopping_cart'] as $key => $product) {
						$count++;
						$total+=$product['quantity']*$product['price'];
						?>
					<div class="order">
						<div>
							<p style="font-weight: bold; font-size:15px;"  > <?php echo $count.'. '.$product['name'];?></p>
							<p>Quantity: <?php echo $product['quantity'];?></p>
							<p> Price= <?php echo $product['quantity'].'x'.$product['price']." =  ". $product['quantity']*$product['price']?>/=</p>
						</div>
						<div>
							<form method="GET" action="menu.php" >
								<input type="hidden" name="id" value="<?php echo $product['id']; ?>">
								<input type="submit" name="remove cart" value="X" class="close">
							</form>
						</div>
					</div><?php
					}
				}
			
					if(!empty($_SESSION['shopping_cart'])){

						?>
					<div style="font-size: 25px;margin-top: 5px;"> Grand Total: <?php echo $total; ?>
					<?php
				}
				?>
			</div>
			<?php
				if(isset($_SESSION['shopping_cart']))
				{
					if(count($_SESSION['shopping_cart'])>0)
					{
						?>
						<div>
							<form action="" method="post">
								<center><button name="placeorder" class="addcart">
									Place Order
								</button></cen ter>
							</form>
						</div>
						<?php
					}
				}
			?>

		</div>
	</div>
</body>
</html>