resource "aws_vpc" "main_vpc" {
  cidr_block           = "10.100.0.0/16"
  enable_dns_hostnames = true

  tags {
    Name = "${var.identifier}"
  }
}

resource "aws_internet_gateway" "gw" {
  vpc_id = "${aws_vpc.main_vpc.id}"

  tags {
    Name = "${var.identifier}"
  }
}

resource "aws_subnet" "subnet_1" {
  vpc_id            = "${aws_vpc.main_vpc.id}"
  cidr_block        = "${var.subnet_1_cidr}"
  availability_zone = "${var.az_1}"

  tags {
    Name = "${var.identifier}"
  }
}

resource "aws_subnet" "subnet_2" {
  vpc_id            = "${aws_vpc.main_vpc.id}"
  cidr_block        = "${var.subnet_2_cidr}"
  availability_zone = "${var.az_2}"

  tags {
    Name = "${var.identifier}"
  }
}

resource "aws_route" "r" {
  route_table_id         = "${aws_vpc.main_vpc.main_route_table_id}"
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = "${aws_internet_gateway.gw.id}"
}
