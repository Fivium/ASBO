FROM centos:centos7

ENV container docker \
    PHP_VERSION="71"

COPY oracle-instantclient12.1-basic-12.1.0.2.0-1.x86_64.rpm /var/
COPY oracle-instantclient12.1-devel-12.1.0.2.0-1.x86_64.rpm /var/


RUN (cd /lib/systemd/system/sysinit.target.wants/; for i in *; do [ $i == \
systemd-tmpfiles-setup.service ] || rm -f $i; done); \
rm -f /lib/systemd/system/multi-user.target.wants/*;\
rm -f /etc/systemd/system/*.wants/*;\
rm -f /lib/systemd/system/local-fs.target.wants/*; \
rm -f /lib/systemd/system/sockets.target.wants/*udev*; \
rm -f /lib/systemd/system/sockets.target.wants/*initctl*; \
rm -f /lib/systemd/system/basic.target.wants/*;\
rm -f /lib/systemd/system/anaconda.target.wants/*;
VOLUME [ "/sys/fs/cgroup" ]
CMD ["/usr/sbin/init"]


RUN yum -y --setopt=tsflags=nodocs update && \
    yum -y --setopt=tsflags=nodocs install httpd && \
    yum clean all

# -----------------------------------------------------------------------------
# Apache + PHP
# -----------------------------------------------------------------------------


RUN rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
&& rpm -Uvh https://mirror.webtatic.com/yum/el7/webtatic-release.rpm


RUN     yum -y update \
        && yum --setopt=tsflags=nodocs -y install \
        gcc \
        gcc-c++ \
        httpd \
        mod_ssl \
        php70w \
        php70w-cli \
        php70w-devel \
        php70w-mysql \
        php70w-pdo \
        php70w-mbstring \
        php70w-soap \
        php70w-gd \
        php70w-xml \
        php70w-pecl-apcu \
        unzip \
        libXrender fontconfig libXext urw-fonts \
        ImageMagick ImageMagick-devel \
        vim \
        mutt \
        && rm -rf /var/cache/yum/* \
        && yum clean all \
        && echo "TraceEnable Off" >> /etc/httpd/conf/httpd.conf

# -----------------------------------------------------------------------------
# Patch emailer setup
# -----------------------------------------------------------------------------
RUN yum install -y openssh-server && \
    yum install -y openssh-clients && \
    yum install -y cpan && \
    yum install -y "perl(Env)"





# -----------------------------------------------------------------------------
# ASBO Set-up
# -----------------------------------------------------------------------------

RUN \
        cd /var/www/html \
        && /usr/bin/curl  https://codeload.github.com/Fivium/ASBO/zip/master > avo.zip \
        && unzip avo.zip \
        && mv /var/www/html/ASBO-master/web/*  /var/www/html/ \
        && mv /var/www/html/ASBO-master/alerts/ /var/www/html/ \
        && rm -rf /var/www/html/ASBO-master




RUN  cd /var/ \
     && yum install -y oracle-instantclient* \
     && echo "#" >> /etc/php.ini \
     && printf "\n" | /usr/bin/pecl install oci8 \
     && echo "extension=oci8.so" >> /etc/php.ini \
     && yum install -y sendmail \
     && cd / \
     && ln -s /var/www/html/ app \
     && chmod +x /app/alerts/check_databases_email.sh \
     && /app/alerts/check_databases_email.sh \
     && yum install -y cronie \
     && yum install -y supervisor \
     && echo 'set smtp_url="smtp://$MUTT_SERVER:$MUTT_PORT/"' > /root/.muttrc \
     && echo 'set from="$MUTT_FROM"' >> /root/.muttrc \
     && echo 'set realname="$MUTT_FROM"' >> /root/.muttrc \
     && chmod +x /app/alerts/patch_checker/patch_parser.pl

#-----------------------------------------------------------------------------
#Setting up SupervisorD to allow multiple foreground processes that acts as a service
#-----------------------------------------------------------------------------

RUN echo "[supervisord]" > /etc/supervisord.conf && \
echo "nodaemon=true" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf && \
echo "[program:httpd]" >> /etc/supervisord.conf && \
echo "command=/usr/sbin/apachectl -D FOREGROUND" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf && \
echo "[program:crond]" >> /etc/supervisord.conf && \
echo "command=/usr/sbin/crond -n" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf && \
echo "[program:string_replacement]" >> /etc/supervisord.conf && \
echo "command=/app/string_replacement.pl" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf && \
echo "[program:sshd]" >> /etc/supervisord.conf && \
echo "command=/usr/sbin/sshd -D" >> /etc/supervisord.conf && \
echo "" >> /etc/supervisord.conf






#-----------------------------------------------------------------------------
#Set up Date and Time environment variable
#-----------------------------------------------------------------------------

ENV TZONE="Europe/London"
RUN echo "${TZONE}" > /etc/timezone \
&& ln -sf /usr/share/zoneinfo/${TZONE} /etc/localtime




#-----------------------------------------------------------------------------
#Set up crontab scheduling
#-----------------------------------------------------------------------------

RUN echo "*/5 * * * * /app/alerts/check_databases_email.sh" > /var/spool/cron/root && \
echo "05 7 * * * /app/alerts/check_databases_email.sh FORCE_SEND_EMAIL" >> /var/spool/cron/root && \
echo "05 7 * * * /app/alerts/send_sql_reports.pl" >> /var/spool/cron/root && \
echo "LANG=\"en_GB.UTF-8\"" > /etc/locale.conf && \
yum install -y wget  && \
yum install -y perl-MIME-Lite && \
yum install -y perl-Time-Piece && \
chmod +x /app/alerts/send_sql_reports.pl && \
rm -f /app/avo.zip



CMD ["/usr/bin/supervisord"]

USER root
HEALTHCHECK CMD curl --fail http://localhost:80 || exit

EXPOSE 80
