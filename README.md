Elastic Connector Alert
- abcdef
- abcdef

How to
---------------------------------------------------------------


to build : ( you must in working folder ) 
> docker build -t phpldapadmin-alpine-httpd .

to Check if success and available in list images :
> docker images

to run to create new container:
> docker run -d -p 80:8080  --name phpldapadmin phpldapadmin-alpine-httpd

to run after create new container
> docker container ls -a

> docker start phpldapadmin
