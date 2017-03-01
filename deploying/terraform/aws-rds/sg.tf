resource "aws_security_group" "default" {
  name        = "main_rds_sg"
  description = "Allow all inbound traffic"
  vpc_id      = "${aws_vpc.main_vpc.id}"

  ingress {
    from_port   = 3306
    to_port     = 3306
    protocol    = "TCP"
    cidr_blocks = ["${var.cidr_blocks}"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags {
    Name = "${var.identifier}"
  }
}
