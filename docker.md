
138.68.184.69
tldr.icu

To allow remote docker daemon connection:

/etc/systemd/system/docker.service.d/docker.conf
[Service]
ExecStart=
ExecStart=/usr/bin/dockerd

/etc/docker/daemon.json
{
"tls": true,
"tlscacert": "/var/www/html/ca.pem",
"tlscert": "/var/www/html/server-cert.pem",
"tlskey": "/var/www/html/server-key.pem",
"tlsverify": true,
"hosts": ["unix:///var/run/docker.sock", "tcp://138.68.184.69:2376"]
}

scp ca.pem root@138.68.184.69:/var/www/html/ca.pem
scp server-cert.pem root@138.68.184.69:/var/www/html/server-cert.pem
scp server-key.pem root@138.68.184.69:/var/www/html/server-key.pem
ssh root@138.68.184.69
sudo systemctl restart docker.service
sudo systemctl daemon-reload

generate certs:
certificates location /var/www/html
-r--r--r-- 1 root root  1952 Oct 29 15:02 ca.pem
-r--r--r-- 1 root root  1952 Oct 29 15:01 server-cert.pem
-r-------- 1 root root  3272 Oct 29 15:01 server-key.pem

manual check:
docker --tlsverify --tlscacert=ca.pem --tlscert=cert.pem --tlskey=key.pem -H=138.68.184.69:2376 ps

create/update docker context:
docker context create icu --docker "host=tcp://138.68.184.69:2376,ca=ca.pem,cert=cert.pem,key=key.pem"
docker context update icu --docker "host=tcp://138.68.184.69:2376,ca=ca.pem,cert=cert.pem,key=key.pem"

