FROM python:3.11-alpine
WORKDIR /python-docker
COPY requirements.txt requirements.txt
RUN pip install .
EXPOSE 8080 
COPY . .
CMD [ "python", "app.py" ]
