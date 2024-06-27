import pytest
import json
from app import app

@pytest.fixture
def client():
    client = app.test_client()
    yield client

def test_add_new_user(client):
    response = client.post(
        "/image_push_acr",
        data=json.dumps({"rg_name": "rg-name", "digest": "sha:xxxxxxxxxxxxxxxxxx", "date": "01/01/2024"}),
        headers={"Content-Type": "application/json"},
    )
    assert response.status_code == 200
    assert response.json == {"rg_name": "rg-name", "digest": "sha:xxxxxxxxxxxxxxxxxx", "date": "01/01/2024"}
