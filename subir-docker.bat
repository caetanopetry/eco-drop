@echo off
setlocal

set "MYSQL_ROOT_PASSWORD=%MYSQL_ROOT_PASSWORD%"
if not defined MYSQL_ROOT_PASSWORD set "MYSQL_ROOT_PASSWORD=ecodrop_local"

docker build -f Dockerfile.mysql -t ecodrop-mysql .
if errorlevel 1 exit /b 1

docker build -f dockerfile.apache -t ecodrop-apache .
if errorlevel 1 exit /b 1

docker network inspect ecodrop-network >nul 2>&1 || docker network create ecodrop-network
if errorlevel 1 exit /b 1

docker volume create ecodrop-mysql-data >nul
if errorlevel 1 exit /b 1

docker rm -f ecodrop-app ecodrop-db >nul 2>&1

docker run -d --name ecodrop-db --restart unless-stopped --network ecodrop-network -p 3306:3306 -e MYSQL_ROOT_PASSWORD="%MYSQL_ROOT_PASSWORD%" -e MYSQL_DATABASE=ecodrop -v ecodrop-mysql-data:/var/lib/mysql ecodrop-mysql
if errorlevel 1 exit /b 1

for /L %%I in (1,1,60) do (
    docker exec ecodrop-db mysqladmin ping -h 127.0.0.1 -uroot -p"%MYSQL_ROOT_PASSWORD%" --silent >nul 2>&1
    if not errorlevel 1 goto mysql_ready
    ping 127.0.0.1 -n 2 >nul
)

echo MySQL nao ficou pronto dentro do tempo esperado.
docker logs ecodrop-db
exit /b 1

:mysql_ready
docker run -d --name ecodrop-app --restart unless-stopped --network ecodrop-network -p 8080:80 -e DB_HOST=ecodrop-db -e DB_PORT=3306 -e DB_NAME=ecodrop -e DB_USER=root -e DB_PASS="%MYSQL_ROOT_PASSWORD%" ecodrop-apache
if errorlevel 1 exit /b 1

echo EcoDrop disponivel em http://localhost:8080/EcoDrop/
start "" http://localhost:8080/EcoDrop/