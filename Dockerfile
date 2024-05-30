FROM python:3.11-alpine
ADD https://netfree.link/dl/unix-ca.sh /home/netfree-unix-ca.sh 
RUN cat  /home/netfree-unix-ca.sh | sh
ENV NODE_EXTRA_CA_CERTS=/etc/ca-bundle.crt
ENV REQUESTS_CA_BUNDLE=/etc/ca-bundle.crt
ENV SSL_CERT_FILE=/etc/ca-bundle.crt
WORKDIR /python-docker
COPY requirements.txt requirements.txt
RUN pip install --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org --root-user-action=ignore -r requirements.txt 
EXPOSE 8080 
COPY . .
CMD [ "python", "app.py" ]
