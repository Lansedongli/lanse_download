#!/usr/bin/env python3
"""
帝国下载系统 V3 — Mock API Server
快速模拟后端接口，用于前端开发调试
"""

import json
import time
import hashlib
import hmac
import base64
import http.server
import threading

PORT = 8080

# ============ Mock 数据 ============
MOCK_DATA = {
    "categories": [
        {"id": 1, "parent_id": 0, "name": "系统工具", "code": "system-tools", "level": 1, "sort": 1, "status": 1, "children": [
            {"id": 2, "parent_id": 1, "name": "系统优化", "code": "sys-optimize", "level": 2, "sort": 1, "status": 1, "children": []},
            {"id": 3, "parent_id": 1, "name": "磁盘工具", "code": "disk-tools", "level": 2, "sort": 2, "status": 1, "children": []},
        ]},
        {"id": 4, "parent_id": 0, "name": "编程开发", "code": "programming", "level": 1, "sort": 2, "status": 1, "children": [
            {"id": 5, "parent_id": 4, "name": "IDE编辑器", "code": "ide", "level": 2, "sort": 1, "status": 1, "children": []},
        ]},
        {"id": 6, "parent_id": 0, "name": "游戏娱乐", "code": "games", "level": 1, "sort": 3, "status": 1, "children": []},
    ],
    "software_list": {
        "total": 128, "page": 1, "limit": 20,
        "list": [
            {"id": 1, "title": "CleanMaster Pro", "subtitle": "系统清理工具", "category_name": "系统优化", "type_name": "本地下载",
             "version": "6.5.2", "file_size": "15.2MB", "download_count": 15230, "status": 1,
             "is_recommend": 1, "is_hot": 1, "is_new": 0, "rating": 4.5, "created_at": "2026-01-15 10:30:00"},
            {"id": 2, "title": "VS Code 2026", "subtitle": "代码编辑器", "category_name": "IDE编辑器", "type_name": "本地下载",
             "version": "1.90.0", "file_size": "88.5MB", "download_count": 45600, "status": 1,
             "is_recommend": 1, "is_hot": 1, "is_new": 0, "rating": 4.8, "created_at": "2026-02-20 14:00:00"},
            {"id": 3, "title": "PhotoMaster AI", "subtitle": "AI图像处理", "category_name": "图形图像", "type_name": "本地下载",
             "version": "3.0.1", "file_size": "256.8MB", "download_count": 8900, "status": 1,
             "is_recommend": 0, "is_hot": 0, "is_new": 1, "rating": 4.2, "created_at": "2026-04-01 09:00:00"},
            {"id": 4, "title": "GameLauncher", "subtitle": "游戏启动器", "category_name": "游戏娱乐", "type_name": "网盘下载",
             "version": "2.1.0", "file_size": "45.0MB", "download_count": 34200, "status": 1,
             "is_recommend": 0, "is_hot": 1, "is_new": 0, "rating": 4.0, "created_at": "2025-12-10 16:20:00"},
            {"id": 5, "title": "DataBackup Tool", "subtitle": "数据备份恢复", "category_name": "磁盘工具", "type_name": "本地下载",
             "version": "4.2.3", "file_size": "32.1MB", "download_count": 6700, "status": 1,
             "is_recommend": 0, "is_hot": 0, "is_new": 0, "rating": 3.8, "created_at": "2025-08-05 11:00:00"},
        ]
    },
    "users": {
        "total": 3650, "page": 1, "limit": 20,
        "list": [
            {"id": 1, "username": "testuser", "email": "test@example.com", "nickname": "测试用户",
             "group_id": 1, "group_name": "普通会员", "points": 150, "status": 1, "total_download": 42,
             "expire_time": None, "created_at": "2026-01-01 08:00:00"},
            {"id": 2, "username": "vipuser", "email": "vip@example.com", "nickname": "VIP用户",
             "group_id": 2, "group_name": "VIP会员", "points": 850, "status": 1, "total_download": 230,
             "expire_time": "2026-12-31 23:59:59", "created_at": "2026-02-15 10:00:00"},
            {"id": 3, "username": "svip_demo", "email": "svip@example.com", "nickname": "SVIP演示",
             "group_id": 3, "group_name": "SVIP会员", "points": 5000, "status": 1, "total_download": 1200,
             "expire_time": "2027-06-30 23:59:59", "created_at": "2026-03-01 12:00:00"},
        ]
    },
    "pointcard_batches": [
        {"id": 1, "batch_no": "BATCH20260501", "total_count": 100, "used_count": 45, "points": 100, "created_at": "2026-05-01 10:00:00"},
        {"id": 2, "batch_no": "BATCH20260502", "total_count": 50, "used_count": 12, "points": 500, "created_at": "2026-05-02 14:00:00"},
    ],
    "pointcard_cards_1": {
        "total": 100,
        "list": [
            {"id": 1, "card_no": "BATCH2026050100000001", "points": 100, "status": 0, "expire_time": "2027-05-01", "used_user_id": 0},
            {"id": 2, "card_no": "BATCH2026050100000002", "points": 100, "status": 0, "expire_time": "2027-05-01", "used_user_id": 0},
            {"id": 3, "card_no": "BATCH2026050100000003", "points": 100, "status": 1, "expire_time": "2027-05-01", "used_user_id": 2, "used_time": "2026-05-03"},
        ]
    },
    "recharges": {
        "total": 520,
        "list": [
            {"id": 1, "order_no": "PC20260503120001ABC123", "user_id": 2, "type": "point_card", "amount": 0, "points": 100,
             "group_id": 0, "group_days": 0, "status": 1, "trade_no": "", "paid_at": "2026-05-03 12:00:00", "created_at": "2026-05-03 12:00:00"},
            {"id": 2, "order_no": "ED20260504150000DEF456", "user_id": 3, "type": "alipay", "amount": 299.00, "points": 3000,
             "group_id": 3, "group_days": 365, "status": 1, "trade_no": "ALIPAY20260504001", "paid_at": "2026-05-04 15:00:00", "created_at": "2026-05-04 15:00:00"},
        ]
    }
}

# JWT Secret
JWT_SECRET = "mock_jwt_secret_for_dev"
JWT_PAYLOAD = {
    "iss": "empiredown", "iat": 1717588800, "exp": 1999999999,
    "user_id": 1, "username": "admin", "group_id": 0, "level": 99, "guard": "admin"
}

def create_token():
    header = base64.urlsafe_b64encode(json.dumps({"alg": "HS256", "typ": "JWT"}).encode()).rstrip(b'=').decode()
    payload = base64.urlsafe_b64encode(json.dumps(JWT_PAYLOAD).encode()).rstrip(b'=').decode()
    sig = hmac.new(JWT_SECRET.encode(), f"{header}.{payload}".encode(), hashlib.sha256).digest()
    sig_b64 = base64.urlsafe_b64encode(sig).rstrip(b'=').decode()
    return f"{header}.{payload}.{sig_b64}"

MOCK_TOKEN = create_token()

class MockHandler(http.server.BaseHTTPRequestHandler):
    def _send_json(self, code, data):
        self.send_response(code)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', '*')
        self.send_header('Access-Control-Allow-Headers', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data, ensure_ascii=False).encode())

    def _success(self, data=None, message="操作成功"):
        self._send_json(200, {"code": 200, "message": message, "data": data or {}, "timestamp": int(time.time())})

    def do_OPTIONS(self):
        self._send_json(204, {})

    def do_GET(self):
        path = self.path.split('?')[0]

        # Auth
        if path == '/admin/api/auth/me':
            self._success({"id": 1, "username": "admin", "nickname": "超级管理员", "role_id": 1, "avatar": ""})
        elif path == '/admin/api/categories':
            self._success(MOCK_DATA["categories"])
        elif path == '/admin/api/software':
            self._success(MOCK_DATA["software_list"])
        elif path.startswith('/admin/api/software/') and path.endswith('/audit'):
            return self.do_PUT()
        elif path.startswith('/admin/api/software/'):
            sid = int(path.split('/')[-1])
            sw = next((s for s in MOCK_DATA["software_list"]["list"] if s["id"] == sid), None)
            self._success(sw or {})
        elif path == '/admin/api/users':
            self._success(MOCK_DATA["users"])
        elif path.startswith('/admin/api/users/'):
            uid = int(path.split('/')[-1])
            u = next((u for u in MOCK_DATA["users"]["list"] if u["id"] == uid), None)
            self._success(u or {})
        elif path == '/admin/api/point-cards/batches':
            self._success(MOCK_DATA["pointcard_batches"])
        elif '/cards' in path and '/export' in path:
            # Export - return CSV
            self.send_response(200)
            self.send_header('Content-Type', 'text/csv')
            self.send_header('Content-Disposition', 'attachment; filename=cards.csv')
            self.end_headers()
            self.wfile.write("卡号,密码,点数,状态\nB001,ABCD1234,100,未使用\n".encode())
        elif '/cards' in path:
            self._success(MOCK_DATA["pointcard_cards_1"])
        elif path == '/admin/api/recharges':
            self._success(MOCK_DATA["recharges"])
        else:
            self._success({})

    def do_POST(self):
        path = self.path.split('?')[0]
        content_len = int(self.headers.get('Content-Length', 0))
        body = json.loads(self.rfile.read(content_len)) if content_len > 0 else {}

        if path == '/admin/api/auth/login':
            self._success({
                "user": {"id": 1, "username": "admin", "nickname": "超级管理员", "role_id": 1},
                "tokens": {"access_token": MOCK_TOKEN, "refresh_token": "mock_refresh", "expires_in": 7200}
            }, "登录成功")
        elif path == '/admin/api/categories':
            self._success({"id": 99, **body}, "创建成功")
        elif path == '/admin/api/software':
            self._success({"id": 99, **body}, "创建成功")
        elif path == '/admin/api/point-cards/batches':
            self._success({"id": 99, "batch_no": f"BATCH{time.strftime('%Y%m%d%H')}", **body}, "批次创建成功")
        elif 'generate' in path:
            self._success({"count": body.get("count", 10)}, f"成功生成{body.get('count', 10)}张点卡")
        elif path == '/admin/api/recharges/manual':
            self._success({"order_no": f"AD{int(time.time())}"}, "手动充值成功")
        else:
            self._success(body)

    def do_PUT(self):
        path = self.path.split('?')[0]
        content_len = int(self.headers.get('Content-Length', 0))
        body = json.loads(self.rfile.read(content_len)) if content_len > 0 else {}

        if path.endswith('/audit'):
            self._success(None, "审核成功")
        else:
            self._success(body, "更新成功")

    def do_DELETE(self):
        self._success(None, "删除成功")

    def log_message(self, format, *args):
        pass  # 静默模式

def run():
    server = http.server.HTTPServer(('0.0.0.0', PORT), MockHandler)
    print(f"🚀 Mock API Server 启动: http://localhost:{PORT}")
    print(f"   Admin API: http://localhost:{PORT}/admin/api/")
    print(f"   Token: {MOCK_TOKEN[:50]}...")
    server.serve_forever()

if __name__ == '__main__':
    run()
