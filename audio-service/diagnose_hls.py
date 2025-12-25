#!/usr/bin/env python3
"""
诊断脚本：检查HLS文件生成和路由问题
"""
import os
import sys
from pathlib import Path

sys.path.insert(0, os.path.dirname(__file__))

from app.config import config

def check_hls_files():
    """检查HLS文件生成情况"""
    print("\n=== HLS文件诊断 ===")
    
    hls_dir = Path(config.HLS_OUTPUT_DIR)
    if not hls_dir.exists():
        print(f"✗ HLS输出目录不存在: {config.HLS_OUTPUT_DIR}")
        return False
    
    print(f"✓ HLS输出目录存在: {config.HLS_OUTPUT_DIR}")
    
    # 列出所有频道目录
    channels = [d for d in hls_dir.iterdir() if d.is_dir()]
    if not channels:
        print("⚠ 没有频道目录")
        return True
    
    print(f"✓ 找到 {len(channels)} 个频道目录")
    
    for channel_dir in channels:
        channel_id = channel_dir.name
        files = list(channel_dir.iterdir())
        
        if not files:
            print(f"  ⚠ 频道 {channel_id}: 没有文件")
            continue
        
        # 统计文件类型
        m3u8_files = [f for f in files if f.suffix == '.m3u8']
        ts_files = [f for f in files if f.suffix == '.ts']
        
        print(f"  频道 {channel_id}:")
        print(f"    - M3U8文件: {len(m3u8_files)}")
        print(f"    - TS文件: {len(ts_files)}")
        
        # 检查文件名格式
        if ts_files:
            sample_file = ts_files[0].name
            expected_prefix = config.HLS_SEGMENT_PREFIX
            if sample_file.startswith(expected_prefix):
                print(f"    ✓ TS文件名格式正确 (前缀: {expected_prefix})")
            else:
                print(f"    ✗ TS文件名格式错误 (期望前缀: {expected_prefix}, 实际: {sample_file})")
        
        # 显示最新的几个文件
        if files:
            recent_files = sorted(files, key=lambda f: f.stat().st_mtime, reverse=True)[:3]
            print(f"    最新文件:")
            for f in recent_files:
                size = f.stat().st_size
                print(f"      - {f.name} ({size} bytes)")

def check_config():
    """检查配置"""
    print("\n=== 配置检查 ===")
    print(f"HLS_ENABLED: {config.HLS_ENABLED}")
    print(f"HLS_OUTPUT_DIR: {config.HLS_OUTPUT_DIR}")
    print(f"HLS_PLAYLIST_NAME: {config.HLS_PLAYLIST_NAME}")
    print(f"HLS_SEGMENT_PREFIX: {config.HLS_SEGMENT_PREFIX}")
    print(f"HLS_SEGMENT_DURATION: {config.HLS_SEGMENT_DURATION}")
    print(f"HLS_SEGMENT_LIST_SIZE: {config.HLS_SEGMENT_LIST_SIZE}")

def main():
    print("=" * 50)
    print("HLS诊断工具")
    print("=" * 50)
    
    check_config()
    check_hls_files()
    
    print("\n" + "=" * 50)
    print("诊断完成")

if __name__ == '__main__':
    main()
