import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

class ActiveStandardBadge extends StatelessWidget {
  final int standardNumber;
  final bool isSelected;
  final VoidCallback onTap;
  final VoidCallback? onRemove;

  const ActiveStandardBadge({
    super.key,
    required this.standardNumber,
    required this.isSelected,
    required this.onTap,
    this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(24),
            child: Container(
              width: 112,
              height: 112,
              decoration: BoxDecoration(
                color: isSelected ? AppColors.primaryBlue : AppColors.surfaceWhite,
                borderRadius: BorderRadius.circular(24),
                border: isSelected
                    ? Border.all(color: AppColors.darkBlue, width: 2)
                    : Border.all(color: AppColors.borderLight, width: 1),
                boxShadow: isSelected
                    ? [
                        BoxShadow(
                          color: AppColors.darkNavy.withValues(alpha: 0.3),
                          blurRadius: 16,
                          offset: const Offset(0, 8),
                        ),
                      ]
                    : [AppShadows.subtleShadow],
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    '$standardNumber',
                    style: TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.w800,
                      color: isSelected
                          ? AppColors.surfaceWhite
                          : AppColors.textPrimary,
                      height: 1.1,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'STANDARD',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.8,
                      color: isSelected
                          ? AppColors.surfaceWhite.withValues(alpha: 0.8)
                          : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),

        // Optional Remove Button (✕)
        if (onRemove != null)
          Positioned(
            top: -4,
            right: -4,
            child: GestureDetector(
              onTap: onRemove,
              child: Container(
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: AppColors.borderLight,
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.close_rounded,
                  size: 14,
                  color: AppColors.error,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class AddStandardButton extends StatelessWidget {
  final VoidCallback onTap;

  const AddStandardButton({super.key, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Container(
          width: 80,
          height: 112,
          decoration: BoxDecoration(
            color: AppColors.lightBlueBg,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: AppColors.primaryBorder, width: 1.5),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(
                  color: AppColors.surfaceWhite,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.add_rounded,
                  color: AppColors.primaryBlue,
                  size: 22,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'ADD',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.8,
                  color: AppColors.primaryBlue,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
